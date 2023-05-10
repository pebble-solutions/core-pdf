<?php

namespace App\MessageHandler;

use App\Message\TraitementFichierMessage;
use phpseclib3\Net\SFTP;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PrivateKey;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;



class TraitementFichierHandler implements MessageHandlerInterface
{

    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function __invoke(TraitementFichierMessage $message)
    {
        
        sleep(30);

        $directory = $message->getDirectory();
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
        if(!$ssh->get("/Work/Convert/$filePath.pdf",$this->parameterBag->get('render_directory')."/$filePath.pdf")){
            throw new \RuntimeException("Impossible de télécharger le fichier");
        }

        $command = "rm /Work/Convert/$filePath.pdf";
        if($ssh->exec($command)){
            throw new \RuntimeException("Problème lors du nettoyage");
        }
        
        $ssh->disconnect();

        $filesystem = new Filesystem();
        $filesystem->remove($this->parameterBag->get('files_directory')."/$directory");

        return true;
    }
}
