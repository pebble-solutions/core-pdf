<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
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
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Operation;

class TraitementController extends AbstractController
{

    #[Route('/upload', name: 'app_upload', methods:['POST'])]
    public function uploadFichier(Request $request,MessageBusInterface $bus,EntityManagerInterface $entityManager): Response
    {
        $fs = new FileSystem();
        $file = $request->files->get('Fichier');
        $header = $request->files->get('Header');
        $footer = $request->files->get('Footer');
        $css = $request->files->get('Style');
        $validExtensions = ['html', 'htm'];

        if (!$request->files->has('Fichier')) {
            return new JsonResponse(['error' =>'Le fichier HTML n\'a pas été transmis.']);
        }
        
        $extensionFile = $file->getClientOriginalExtension();

        if (!in_array($extensionFile, $validExtensions)) {
            return new JsonResponse(['error' => 'Le fichier doit être un fichier HTML ou HTM.']);
        }

        $extensionHeader = $header ? $header->getClientOriginalExtension() : null;
        if (isset($extensionHeader) && !in_array($extensionHeader, $validExtensions)) {
            return new JsonResponse(['error' => 'Le fichier de l\'en-tête doit être un fichier HTML ou HTM.']);
        }

        $extensionFooter = $footer ? $footer->getClientOriginalExtension() : null;
        if (isset($extensionFooter) && !in_array($extensionFooter, $validExtensions)) {
            return new JsonResponse(['error' => 'Le fichier de pied de page doit être un fichier HTML ou HTM.']);
        }

        $extensionCss = $css ? $css->getClientOriginalExtension() : null;
        if (isset($extensionFooter) && !in_array($css, ["css"])) {
            return new JsonResponse(['error' => 'Le fichier de style doit être un fichier CSS']);
        }

        $id= uniqid();
        $directory= 'uploads/'.$id;
        $fileName = $id . '.html';
        $headerName = 'header.html';
        $footerName = 'footer.html';
        $cssName = 'style.css';
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
        if($css){
            $css->move(
                $directory,
                $cssName
            );
        }else{
            $fs->copy($this->getParameter('default_file_path'),$directory."/$cssName");
        }

        $operation = new Operation();
        $operation->setNom($fileName);
        $operation->setEtat('Running');
        $entityManager->persist($operation);
        $entityManager->flush();
        $operationId = $operation->getId();
        $bus->dispatch(new TraitementFichierMessage($operationId,$id,$fileName,$headerName,$footerName,$cssName));

        return new JsonResponse(['success' => 'L\'opération a été enregistrée avec succès. Votre pdf va être généré dans quelques instants.']);
    }



    #[Route('/fichiers', name: 'app_liste_fichiers')]
    public function listeFichiers(): Response
    {
        $fichiers = scandir($this->getParameter('render_directory'));
        $fichiers = array_filter($fichiers, function ($fichier) {
            return $fichier !== '.' && $fichier !== '..';
        });
    
        /*
        return $this->render('traitement/liste_fichiers.html.twig', [
            'fichiers' => $fichiers,
        ]);*/

        $data = [];
        foreach ($fichiers as $fichier) {
            $data[] = [
                'nom' => $fichier,
            ];
        }

        return new Response(json_encode($data), 200, [
            'Content-Type' => 'application/json'
        ]);
    }


    #[Route('/operations', name: 'app_liste_operations')]
    public function listeOperations(EntityManagerInterface $entityManager): Response
    {
        $operations = $entityManager->getRepository(Operation::class)->findAll();
    
        $data = [];
        foreach ($operations as $operation) {
            $data[] = [
                'id' => $operation->getId(),
                'fichier' => $operation->getNom(),
                'etat' => $operation->getEtat(),
            ];
        }
    
        return new Response(json_encode($data), 200, [
            'Content-Type' => 'application/json'
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
    
    #[Route('/fichier/del/{id}/', name: 'app_supprimer_fichier', methods: ['DELETE'])]
    public function supprimerFichier(EntityManagerInterface $entityManager,int $id): Response
    {
        $operation = $entityManager->getRepository(Operation::class)->find($id);
        $fichierPath = $this->getParameter('render_directory') . '/' . $operation->getNom() .'.pdf';
        if (file_exists($fichierPath)) {
            unlink($fichierPath);
    
            $operation = $entityManager->getRepository(Operation::class)->find($id);
            $operation->setEtat('Deleted');
            $entityManager->persist($operation);
            $entityManager->flush();
    
            $message = 'Le fichier a été supprimé avec succès.';
            $response = new JsonResponse(['message' => $message], Response::HTTP_OK);
        } else {
            $message = 'Une erreur est survenue lors de la suppression du fichier.';
            $response = new JsonResponse(['message' => $message], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    
        return $response;
    }

}

