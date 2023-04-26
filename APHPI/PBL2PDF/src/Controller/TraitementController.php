<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class TraitementController extends AbstractController
{
    #[Route('/traitement', name: 'app_traitement')]
    public function index(Request $request): Response
    {
        $form = $this->createFormBuilder()
            ->add('file', FileType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            $fileName = uniqid() . '.' . $file->guessExtension();

            $file->move(
                $this->getParameter('files_directory'),
                $fileName
            );

            // TODO: Appel à la méthode de traitement du fichier
            $this->traiterFichier($fileName);

            return $this->redirectToRoute('app_traitement');
        }

        return $this->render('traitement/upload.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function traiterFichier(string $filePath): string
    {
        // TODO: Traitement simulé du fichier HTML
        // Ici, nous allons simplement retourner le contenu du fichier tel quel.

        return file_get_contents($this->getParameter('files_directory') . '/' . $filePath);
    }


    #[Route('/fichiers', name: 'app_fichiers')]
    public function listeFichiers(): Response
    {
        $fichiers = scandir($this->getParameter('files_directory'));
        $fichiers = array_filter($fichiers, function ($fichier) {
            return $fichier !== '.' && $fichier !== '..';
        });
    
        return $this->render('traitement/liste_fichiers.html.twig', [
            'fichiers' => $fichiers,
        ]);
    }
    #[Route('/fichiers/{nomFichier}', name: 'app_fichier')]
    public function afficherFichier(string $nomFichier): Response
    {
        $contenu = file_get_contents($this->getParameter('files_directory') . '/' . $nomFichier);
        return new Response($contenu);
    }
}

