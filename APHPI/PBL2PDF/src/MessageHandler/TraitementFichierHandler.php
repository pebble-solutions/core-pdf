<?php

namespace App\MessageHandler;

use App\Controller\TraitementController;
use App\Message\TraitementFichierMessage;
use phpseclib3\Net\SFTP;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PrivateKey;
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
        $filesystem = new Filesystem();
        
        $cssPath = $message->getCss();
        $id = $message->getId();
        $directory = $message->getDirectory();
        $filePath = $message->getFilePath();
        $headerPath = $message->getHeaderPath();
        $footerPath = $message->getFooterPath();

        $ssh = new SFTP('html2pdf');
        $privateKey = RSA::load(file_get_contents('/root/.ssh/id_rsa'));
        $publicKey = RSA::load(file_get_contents('/root/.ssh/id_rsa.pub'));
        
        if (!$ssh->login('root', $privateKey)) {
            throw new \RuntimeException('Login Failed: ' . $ssh->getLastError());
        }

        // Chemin du répertoire distant et local
        $remoteDir = '/Work/Convert/';
        $localPath = $this->parameterBag->get('files_directory') . "/$directory/$filePath";

        // Vérifier si le répertoire distant existe, sinon le créer
        if (!$ssh->is_dir($remoteDir)) {
            $ssh->mkdir($remoteDir, -1, true);
        }

        // Téléversement du fichier vers le conteneur ubuntu
        if (!$ssh->put($remoteDir . $filePath, $localPath, SFTP::SOURCE_LOCAL_FILE)) {
            throw new \RuntimeException("Le versement du fichier a échoué. Erreur : " . $ssh->getLastSFTPError());
        }

        // Chemin du répertoire distant et local pour les fichiers divers
        $remoteDir = '/Work/Divers/';
        $localHeader = $this->parameterBag->get('files_directory') . "/$directory/$headerPath";
        $localFooter = $this->parameterBag->get('files_directory') . "/$directory/$footerPath";
        $localCss = $this->parameterBag->get('files_directory') . "/$directory/$cssPath";

        // Vérifier si le répertoire distant existe, sinon le créer
        if (!$ssh->is_dir($remoteDir)) {
            $ssh->mkdir($remoteDir, -1, true);
        }

        if (!$ssh->is_dir("$remoteDir/stylesheet")) {
            $ssh->mkdir("$remoteDir/stylesheet", -1, true);
        }

        // Téléversement des fichiers divers vers le conteneur ubuntu
        if (!$ssh->put($remoteDir . $headerPath, $localHeader, SFTP::SOURCE_LOCAL_FILE)) {
            throw new \RuntimeException("Le versement du header a échoué. Erreur : " . $ssh->getLastSFTPError());
        }

        if (!$ssh->put($remoteDir . $footerPath, $localFooter, SFTP::SOURCE_LOCAL_FILE)) {
            throw new \RuntimeException("Le versement du footer a échoué. Erreur : " . $ssh->getLastSFTPError());
        }

        if (!$ssh->put($remoteDir . "/stylesheet/$cssPath", $localCss, SFTP::SOURCE_LOCAL_FILE)) {
            throw new \RuntimeException("Le versement du style a échoué. Erreur : " . $ssh->getLastSFTPError());
        }

        $escapedFilePath = escapeshellarg("/Work/Convert/$filePath");
        $command = "node /Work/convert.js $escapedFilePath $escapedFilePath.pdf /Work/Divers/header.html /Work/Divers/footer.html";
        
        if (!$ssh->exec($command)) {
            throw new \RuntimeException("Problème lors de la conversion du fichier");
        }

        sleep(10);

        // Téléchargement du PDF depuis le conteneur ubuntu
        if (!$ssh->get("/Work/Convert/$filePath.pdf", $this->parameterBag->get('render_directory') . "/$filePath.pdf")) {
            throw new \RuntimeException("Impossible de télécharger le fichier");
        }

        // Suppression des répertoires distants
        $ssh->exec("rm -rf /Work/Convert");
        $ssh->exec("rm -rf /Work/Divers");

        $ssh->disconnect();

        // Suppression du répertoire local
        $filesystem->remove($this->parameterBag->get('files_directory') . "/$directory");

        // Mise à jour de l'opération
        $this->updateOperation($id, $this->parameterBag->get('render_directory') . "/$filePath.pdf");

        return true;
    }

    private function updateOperation(string $id, string $pdfFilePath)
    {
        $operation = $this->em->getRepository(Operation::class)->find($id);
        $operation->setEtat('Finished');
    
        // Récupérer le contenu du fichier PDF en base64
        $fileContents = base64_encode(file_get_contents($pdfFilePath));
        $operation->setContenuBase64($fileContents);
    
        $this->em->persist($operation);
        $this->em->flush();
    }
}
