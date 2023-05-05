<?php

namespace App\MessageHandler;

use App\Message\TraitementFichierMessage;
use phpseclib3\Net\SFTP;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PrivateKey;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class TraitementFichierHandler implements MessageHandlerInterface
{


    public function __invoke(TraitementFichierMessage $message)
    {
        dump($message);
        $filePath = $message->getFilePath();
        $headerPath = $message->getHeaderPath();
        $footerPath = $message->getFooterPath();

        $ssh = new SFTP('html2pdf');
        $privateKey = RSA::load(file_get_contents('/var/www/html/id_rsa'));
        $publicKey = RSA::load(file_get_contents('/var/www/html/id_rsa.pub'));
    


        if (!$ssh->login('root', $privateKey)) {
            throw new \RuntimeException('Login Failed :' .$ssh->getLastError());
        }

        // Chemin du fichier distant et local
        $remoteDir = '/Work/Convert/';
        $localPath = $this->getParameter('files_directory')."/$filePath";

        // Vérifier si le répertoire distant existe, sinon le créer
        if (!$ssh->is_dir($remoteDir)) {
            $ssh->mkdir($remoteDir, -1, true);
        }

        // Téléversement du fichier le conteneur ubuntu
        if(!$ssh->put($remoteDir."/$filePath", $localPath, SFTP::SOURCE_LOCAL_FILE)){
            throw new \RuntimeException("Le versement du fichier a echoué. Erreur : " . $ssh->getLastSFTPError());

        }

        $remoteDir = '/Work/Divers/';
        $localHeader = $this->getParameter('files_directory')."/$headerPath";
        $localFooter = $this->getParameter('files_directory')."/$footerPath";


        // Vérifier si le répertoire distant existe, sinon le créer
        if (!$ssh->is_dir($remoteDir)) {
            $ssh->mkdir($remoteDir, -1, true);
        }


        // Téléversement des fichiers divers sur le conteneur ubuntu
        if(!$ssh->put($remoteDir."/$headerPath", $localHeader, SFTP::SOURCE_LOCAL_FILE)){
            throw new \RuntimeException("Le versement du header a echoué. Erreur : " . $ssh->getLastSFTPError());
        }

        // Téléversement des fichiers divers sur le conteneur ubuntu
        if(!$ssh->put($remoteDir."/$footerPath", $localFooter, SFTP::SOURCE_LOCAL_FILE)){
            throw new \RuntimeException("Le versement du footer a echoué. Erreur : " . $ssh->getLastSFTPError());
        }

        // Exécution du script run.sh sur le conteneur ubuntu
        $command = '/Work/run.sh';
        if(!$ssh->exec($command)){
            throw new \RuntimeException("Problème lors de la conversion du fichier");
        }
        
        //téléchargement du pdf depuis le conteneur ubuntu
        if(!$ssh->get("/Work/Convert/$filePath.pdf",$this->getParameter('files_directory')."/$filePath.pdf")){
            throw new \RuntimeException("Impossible de télécharger le fichier");
        }

        $command = "rm /Work/Convert/$filePath.pdf";
        if($ssh->exec($command)){
            throw new \RuntimeException("Problème lors du nettoyage");
        }
        
        $ssh->disconnect();

        return true;
    }
}
