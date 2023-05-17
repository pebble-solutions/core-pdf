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

    public function __construct(ParameterBagInterface $parameterBag,EntityManagerInterface $em)
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
            throw new \RuntimeException('Login Failed :' .$ssh->getLastError());
        }

        // Chemin du fichier distant et local
        $remoteDir = '/Work/Convert/';
        $localPath = $this->parameterBag->get('files_directory')."/$directory"."/$filePath";

        // Vérifier si le répertoire distant existe, sinon le créer
        if (!$ssh->is_dir($remoteDir)) {
            $ssh->mkdir($remoteDir, -1, true);
        }

        
        // Téléversement du fichier le conteneur ubuntu
        if(!$ssh->put($remoteDir."/$filePath", $localPath, SFTP::SOURCE_LOCAL_FILE)){
            throw new \RuntimeException("Le versement du fichier a echoué. Erreur : " . $ssh->getLastSFTPError());

        }

        

        $remoteDir = '/Work/Divers/';
        $localHeader = $this->parameterBag->get('files_directory')."/$directory"."/$headerPath";
        $localFooter = $this->parameterBag->get('files_directory')."/$directory"."/$footerPath";
        $localCss = $this->parameterBag->get('files_directory')."/$directory"."/$cssPath";

        // Vérifier si le répertoire distant existe, sinon le créer
        if (!$ssh->is_dir($remoteDir)) {
            $ssh->mkdir($remoteDir, -1, true);
        }

        if (!$ssh->is_dir("$remoteDir/stylesheet")) {
            $ssh->mkdir("$remoteDir/stylesheet", -1, true);
        }


        // Téléversement des fichiers divers sur le conteneur ubuntu
        if(!$ssh->put($remoteDir."/$headerPath", $localHeader, SFTP::SOURCE_LOCAL_FILE)){
            throw new \RuntimeException("Le versement du header a echoué. Erreur : " . $ssh->getLastSFTPError());
        }

        // Téléversement des fichiers divers sur le conteneur ubuntu
        if(!$ssh->put($remoteDir."/$footerPath", $localFooter, SFTP::SOURCE_LOCAL_FILE)){
            throw new \RuntimeException("Le versement du footer a echoué. Erreur : " . $ssh->getLastSFTPError());
        }

        // Téléversement des fichiers divers sur le conteneur ubuntu
        if(!$ssh->put($remoteDir."/stylesheet/$cssPath", $localCss, SFTP::SOURCE_LOCAL_FILE)){
            throw new \RuntimeException("Le versement du style a echoué. Erreur : " . $ssh->getLastSFTPError());
        }
        
        // Exécution du script run.sh sur le conteneur ubuntu
   
        $command="node /Work/convert.js  /Work/Convert/$filePath /Work/Convert/$filePath.pdf /Work/Divers/header.html /Work/Divers/footer.html";

        if(!$ssh->exec($command)){
            throw new \RuntimeException("Problème lors de la conversion du fichier");
        }
        
        sleep(10);
        
        //téléchargement du pdf depuis le conteneur ubuntu
        if(!$ssh->get("/Work/Convert/$filePath.pdf",$this->parameterBag->get('render_directory')."/$filePath.pdf")){
            throw new \RuntimeException("Impossible de télécharger le fichier");
        }

        $command = "rm -rf /Work/Convert";
        if($ssh->exec($command)){
            throw new \RuntimeException("Problème lors du nettoyage");
        }

        $command = "rm -rf /Work/Divers";
        if($ssh->exec($command)){
            throw new \RuntimeException("Problème lors du nettoyage");
        }
        
        $ssh->disconnect();

        
        $filesystem->remove($this->parameterBag->get('files_directory')."/$directory");

        $this->updateOperation($this->em,$id);

        return true;
    }
    
    private function updateOperation(EntityManagerInterface $entityManager,string $id) {
        $operation = $entityManager->getRepository(Operation::class)->find($id);
        $operation->setEtat('Finished');

        $entityManager->persist($operation);
        $entityManager->flush();
    }
}
