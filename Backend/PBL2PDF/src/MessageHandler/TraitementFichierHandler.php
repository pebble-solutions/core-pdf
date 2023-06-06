<?php

namespace App\MessageHandler;


use App\Message\TraitementFichierMessage;
use phpseclib3\Net\SFTP;
use phpseclib3\Crypt\RSA;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Operation;

class TraitementFichierHandler implements MessageHandlerInterface
{
    private $parameterBag;
    private $em;

    public function __construct(ParameterBagInterface $parameterBag, EntityManagerInterface $em)
    {
        $this->parameterBag = $parameterBag;
        $this->em = $em;
    }

    public function __invoke(TraitementFichierMessage $message)
    {
        // La méthode __invoke est appelée lorsqu'un message TraitementFichierMessage est traité

        // Création d'une instance de Filesystem
        $filesystem = new Filesystem();

        // Récupération des données du message
        $cssPath = $message->getCss();
        $id = $message->getId();
        $directory = $message->getDirectory();
        $filePath = $message->getFilePath();
        $headerPath = $message->getHeaderPath();
        $footerPath = $message->getFooterPath();

        // Création d'une instance de SFTP et chargement des clés privée et publique RSA
        $ssh = new SFTP('html2pdf');
        $privateKey = RSA::load(file_get_contents('/root/.ssh/id_rsa'));
        $publicKey = RSA::load(file_get_contents('/root/.ssh/id_rsa.pub'));
        
        // Tentative de connexion au serveur SFTP
        if (!$ssh->login('root', $privateKey)) {
            throw new \RuntimeException('Login Failed: ' . $ssh->getLastError());
        }

        // Chemin du répertoire distant et local pour le fichier principal à convertir
        $remoteDir = '/Work/Convert/';
        $localPath = $this->parameterBag->get('files_directory') . "/$directory/$filePath";

        // Vérification de l'existence du répertoire distant, sinon création
        if (!$ssh->is_dir($remoteDir)) {
            $ssh->mkdir($remoteDir, -1, true);
        }

        // Transfert du fichier principal vers le conteneur Ubuntu via SFTP
        if (!$ssh->put($remoteDir . $filePath, $localPath, SFTP::SOURCE_LOCAL_FILE)) {
            throw new \RuntimeException("Le versement du fichier a échoué. Erreur : " . $ssh->getLastSFTPError());
        }

        // Chemin du répertoire distant et local pour les fichiers divers (header, footer, CSS)
        $remoteDir = '/Work/Divers/';
        $localHeader = $this->parameterBag->get('files_directory') . "/$directory/$headerPath";
        $localFooter = $this->parameterBag->get('files_directory') . "/$directory/$footerPath";
        $localCss = $this->parameterBag->get('files_directory') . "/$directory/$cssPath";

        // Vérification de l'existence du répertoire distant, sinon création
        if (!$ssh->is_dir($remoteDir)) {
            $ssh->mkdir($remoteDir, -1, true);
        }

        // Vérification de l'existence du sous-répertoire stylesheet, sinon création
        if (!$ssh->is_dir("$remoteDir/stylesheet")) {
            $ssh->mkdir("$remoteDir/stylesheet", -1, true);
        }

        // Transfert des fichiers divers vers le conteneur Ubuntu via SFTP
        if (!$ssh->put($remoteDir . $headerPath, $localHeader, SFTP::SOURCE_LOCAL_FILE)) {
            throw new \RuntimeException("Le versement du header a échoué. Erreur : " . $ssh->getLastSFTPError());
        }

        if (!$ssh->put($remoteDir . $footerPath, $localFooter, SFTP::SOURCE_LOCAL_FILE)) {
            throw new \RuntimeException("Le versement du footer a échoué. Erreur : " . $ssh->getLastSFTPError());
        }

        if (!$ssh->put($remoteDir . "/stylesheet/$cssPath", $localCss, SFTP::SOURCE_LOCAL_FILE)) {
            throw new \RuntimeException("Le versement du style a échoué. Erreur : " . $ssh->getLastSFTPError());
        }

        // Construction de la commande pour exécuter la conversion du fichier
        $escapedFilePath = escapeshellarg("/Work/Convert/$filePath");
        $command = "node /Work/convert.js $escapedFilePath $escapedFilePath.pdf /Work/Divers/header.html /Work/Divers/footer.html";
        
        // Exécution de la commande sur le serveur via SSH
        if (!$ssh->exec($command)) {
            throw new \RuntimeException("Problème lors de la conversion du fichier");
        }

        // Attente de 10 secondes pour laisser le temps à la conversion de s'effectuer
        sleep(10);

        // Téléchargement du fichier PDF converti depuis le conteneur Ubuntu vers le répertoire de rendu local
        if (!$ssh->get("/Work/Convert/$filePath.pdf", $this->parameterBag->get('render_directory') . "/$filePath.pdf")) {
            throw new \RuntimeException("Impossible de télécharger le fichier");
        }

        // Suppression des répertoires distants sur le serveur
        $ssh->exec("rm -rf /Work/Convert");
        $ssh->exec("rm -rf /Work/Divers");

        // Déconnexion du serveur SFTP
        $ssh->disconnect();

        // Suppression du répertoire local contenant les fichiers d'origine
        $filesystem->remove($this->parameterBag->get('files_directory') . "/$directory");

        // Mise à jour de l'opération dans la base de données
        $this->updateOperation($id, $this->parameterBag->get('render_directory') . "/$filePath.pdf");

        return true;
    }

    private function updateOperation(string $id, string $pdfFilePath)
    {
        // Met à jour l'opération dans la base de données avec l'état 'Finished' et le contenu du fichier PDF converti en base64

        $operation = $this->em->getRepository(Operation::class)->find($id);
        $operation->setEtat('Finished');
    
        // Récupérer le contenu du fichier PDF en base64
        $fileContents = base64_encode(file_get_contents($pdfFilePath));
        $operation->setContenuBase64($fileContents);
    
        $this->em->persist($operation);
        $this->em->flush();
    }
}
