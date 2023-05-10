<?php

namespace App\Controller;


use App\Message\TraitementFichierMessage;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use phpseclib3\Net\SFTP;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PrivateKey;
use Symfony\Component\Messenger\MessageBusInterface;


class TraitementController extends AbstractController
{
    #[Route('/traitement', name: 'app_traitement')]
    public function index(Request $request,MessageBusInterface $bus): Response
    {
        $fs = new FileSystem();

        $form = $this->createFormBuilder()
            ->add('file', FileType::class)
            ->add('header', FileType::class, [
                'required' => false,
            ])
            ->add('footer', FileType::class, [
                'required' => false,
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            $header = $form->get('header')->getData();
            $footer = $form->get('footer')->getData();


            $validExtensions = ['html', 'htm'];

            $extensionFile = $form->get('file')->getData()->getClientOriginalExtension();
            if (!in_array($extensionFile, $validExtensions)) {
                throw new \RuntimeException("Le fichier doit être un fichier HTML ou HTM. $extensionFile");
            }
            
            $extensionHeader = $form->get('header')->getData() ? $form->get('header')->getData()->getClientOriginalExtension() : null;
            if (isset($extensionHeader) && !in_array($extensionHeader, $validExtensions)) {
                throw new \RuntimeException("Le fichier de l'en-tête doit être un fichier HTML ou HTM.");
            }
            
            $extensionFooter = $form->get('footer')->getData() ? $form->get('footer')->getData()->getClientOriginalExtension() : null;
            if (isset($extensionFooter) && !in_array($extensionFooter, $validExtensions)) {
                throw new \RuntimeException("Le fichier de pied de page doit être un fichier HTML ou HTM.");
            }
            
            $id= uniqid();
            $directory= 'uploads/'.$id;
            $fileName = $id . '.html';
            $headerName = 'header.html';
            $footerName = 'footer.html';

            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }


            $file->move(
                $directory,
                $fileName
            );

            if($header){
                $header->move(
                    $directory,
                    $headerName
                );
            }else{
                $fs->copy($this->getParameter('default_file_path'),$directory."/$headerName");
            }
            if($footer){
                $footer->move(
                    $directory,
                    $footerName
                );
            }else{
                $fs->copy($this->getParameter('default_file_path'),$directory."/$footerName");
            }


            $bus->dispatch(new TraitementFichierMessage($id,$fileName,$headerName,$footerName));


            $this->addFlash('success', 'Le fichier a été téléchargé avec succès.');

            /*
            unlink($this->getParameter('files_directory') . '/' . $fileName);
            unlink($this->getParameter('files_directory') . '/' . $headerName);
            unlink($this->getParameter('files_directory') . '/' . $footerName);
            */

            return $this->redirectToRoute('app_liste_fichiers');
        }

        return $this->render('traitement/upload.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/fichiers', name: 'app_fichiers')]
    public function listeFichiers(): Response
    {
        $fichiers = scandir($this->getParameter('render_directory'));
        $fichiers = array_filter($fichiers, function ($fichier) {
            return $fichier !== '.' && $fichier !== '..';
        });
    
        return $this->render('traitement/liste_fichiers.html.twig', [
            'fichiers' => $fichiers,
        ]);
    }
    
    #[Route('/fichier/{nomFichier}', name: 'app_fichier')]
    public function afficherFichier(Request $request, string $nomFichier): Response
    {
        $extension = $request->query->get('extension');
        $filePath = $this->getParameter('render_directory') . '/' . $nomFichier . '.html.' . $extension;
    
        $response = new Response();
        $response->headers->set('Content-Type', 'application/pdf');
        $response->setContent(file_get_contents($filePath));
    
        return $response;
    }
    
    #[Route('/fichier/{nomFichier}/supprimer', name: 'app_supprimer_fichier', methods: ['POST'])]
    public function supprimerFichier(string $nomFichier): Response
    {
        $fichierPath = $this->getParameter('render_directory') . '/' . $nomFichier .'.html.pdf';
        if (file_exists($fichierPath)) {
            unlink($fichierPath);
            $this->addFlash('success', 'Le fichier a été supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression du fichier.');
        }
    
        return $this->redirectToRoute('app_fichiers');
    }
    

}

