<?php

namespace App\Controller;

use App\Entity\Recette;
use App\Entity\Ingredient;
use App\Repository\RecetteRepository;
use App\Repository\PlatRepository;
use App\Repository\IngredientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/api/recettes')]
class RecetteController extends AbstractController
{
    private function formatRecetteDetails($recette): array
    {
        $plat = $recette->getPlat();
        $ingredient = $recette->getIngredient();
        $unite = $ingredient->getUnite();
        
        return [
            'id' => $recette->getId(),
            'plat' => [
                'id' => $plat->getId(),
                'nom' => $plat->getNom(),
                'sprite' => $plat->getSprite(),
                'prix' => $plat->getPrix(),
                'tempsCuisson' => $plat->getTempsCuisson() ? $plat->getTempsCuisson() : null
            ],
            'ingredient' => [
                'id' => $ingredient->getId(),
                'nom' => $ingredient->getNom(),
                'sprite' => $ingredient->getSprite(),
                'unite' => [
                    'id' => $unite->getId(),
                    'nom' => $unite->getNom()
                ]
            ],
            'quantite' => $recette->getQuantite()
        ];
    }

    #[Route('', name: 'api_recettes_list', methods: ['GET'])]
    public function list(RecetteRepository $recetteRepository): JsonResponse
    {
        try {
            $recettes = $recetteRepository->findAll();
            $response = $this->json(array_map(
                [$this, 'formatRecetteDetails'],
                $recettes
            ));
        } catch (\Exception $e) {
            $response = $this->json([
                'error' => 'An error occurred while fetching recipes',
                'message' => $e->getMessage()
            ], 500);
        }

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        
        return $response;
    }

    #[Route('', name: 'api_recettes_create', methods: ['POST'])]
    public function create(
        Request $request, 
        RecetteRepository $recetteRepository,
        PlatRepository $platRepository,
        IngredientRepository $ingredientRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['platId']) || !isset($data['ingredientId']) || !isset($data['quantite'])) {
            $response = $this->json([
                'error' => 'Missing required fields'
            ], 400);
        } else {
            try {
                $plat = $platRepository->find($data['platId']);
                $ingredient = $ingredientRepository->find($data['ingredientId']);
                
                if (!$plat || !$ingredient) {
                    $response = $this->json([
                        'error' => 'Plat or Ingredient not found'
                    ], 404);
                } else {
                    $recette = new Recette();
                    $recette->setPlat($plat)
                           ->setIngredient($ingredient)
                           ->setQuantite($data['quantite']);
                    
                    $recetteRepository->save($recette);
                    
                    $response = $this->json($this->formatRecetteDetails($recette));
                }
            } catch (\Exception $e) {
                $response = $this->json([
                    'error' => 'An error occurred while creating the recipe',
                    'message' => $e->getMessage()
                ], 500);
            }
        }

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        
        return $response;
    }

    // #[Route('/plat/{platId}', name: 'api_recettes_by_plat', methods: ['GET'])]
    // public function getByPlat(int $platId, RecetteRepository $recetteRepository): JsonResponse
    // {
    //     try {
    //         $recettes = $recetteRepository->findByPlatId($platId);
    //         $response = $this->json(array_map(
    //             [$this, 'formatRecetteDetails'],
    //             $recettes
    //         ));
    //     } catch (\Exception $e) {
    //         $response = $this->json([
    //             'error' => 'An error occurred while fetching recipes',
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }

    //     $response->headers->set('Access-Control-Allow-Origin', '*');
    //     $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    //     $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        
    //     return $response;
    // }

    #[Route('', name: 'api_recettes_options', methods: ['OPTIONS'])]
    public function options(): JsonResponse
    {
        $response = new JsonResponse(['status' => 'ok']);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        return $response;
    }

    #[Route('/listV2', name: 'list_recettes', methods: ['GET'])]
    public function listRecettes(RecetteRepository $recetteRepository): JsonResponse
    {
        $recettes = $recetteRepository->findAll();
    
        $result = [];
        foreach ($recettes as $recette) {
            $plat = $recette->getPlat();
            $ingredient = $recette->getIngredient();
    
            if ($plat) {
                $platId = $plat->getId();
    
                if (!isset($result[$platId])) {
                    $result[$platId] = [
                        'id' => $platId,
                        'nom' => $plat->getNom(),
                        'sprite' => $plat->getSprite(),
                        'prix' => $plat->getPrix(),
                        'tempsCuisson' => $plat->getTempsCuisson(),
                        'ingredients' => []
                    ];
                }
    
                if ($ingredient) {
                    $result[$platId]['ingredients'][] = [
                        'id' => $ingredient->getId(),
                        'nom' => $ingredient->getNom(),
                        'sprite' => $ingredient->getSprite(),
                        'unite' => [
                            'id' => $ingredient->getUnite()->getId(),
                            'nom' => $ingredient->getUnite()->getNom(),
                        ],
                        'quantite' => $recette->getQuantite(),
                    ];
                }
            }
        }
    
        $result = array_values($result);
    
        return $this->json($result);
    }

    #[Route('/plat/{platId}', name: 'get_recettes_by_plat', methods: ['GET'])]
    public function getRecettesByPlatId(int $platId, RecetteRepository $recetteRepository): JsonResponse
    {
        // Récupérer toutes les recettes pour le plat donné
        $recettes = $recetteRepository->findBy(['plat' => $platId]);
    
        $result = [];
        foreach ($recettes as $recette) {
            $plat = $recette->getPlat();
            $ingredient = $recette->getIngredient(); // Récupérer un seul ingrédient
    
            if ($plat) {
                $platId = $plat->getId();
                // Si le plat n'est pas encore dans le résultat, l'ajouter
                if (!isset($result[$platId])) {
                    $result[$platId] = [
                        'platId' => $platId,
                        'platNom' => $plat->getNom(),
                        'platSprite' => $plat->getSprite(),
                        'platPrix' => $plat->getPrix(),
                        'platTempsCuisson' => $plat->getTempsCuisson(),
                        'ingredients' => []
                    ];
                }
    
                if ($ingredient) {
                    $result[$platId]['ingredients'][] = [
                        'id' => $ingredient->getId(),
                        'nom' => $ingredient->getNom(),
                        'sprite' => $ingredient->getSprite(),
                        'unite' => [
                            'id' => $ingredient->getUnite()->getId(),
                            'nom' => $ingredient->getUnite()->getNom(),
                        ],
                        'quantite' => $recette->getQuantite(),
                    ];
                }
            }
        }
    
        $result = array_values($result);  
        return $this->json($result);
    }

    #[Route('/{id}', name: 'update_recette', methods: ['PUT'])]
    public function updateRecette(int $id, Request $request, RecetteRepository $recetteRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $recette = $recetteRepository->find($id);
        if (!$recette) {
            return $this->json(['status' => 'error', 'message' => 'Recette non trouvée'], 404);
        }
    
        $data = json_decode($request->getContent(), true);
        
        error_log(print_r($data, true));
    
        // Mettre à jour les ingrédients
        if (isset($data['ingredients'])) {
            foreach ($data['ingredients'] as $ingredientData) {
                $ingredientId = $ingredientData['id'] ?? null;
                $quantite = $ingredientData['quantite'] ?? null;
    
                if ($ingredientId && $quantite) {
                    // Récupérer l'ingrédient associé à la recette
                    $ingredient = $entityManager->getRepository(Ingredient::class)->find($ingredientId);
                    if ($ingredient) {
                        error_log("Mise à jour de l'ingrédient ID: $ingredientId avec quantité: $quantite");
                        
                        foreach ($recette->getIngredient() as $recetteIngredient) {
                            if ($recetteIngredient->getId() === $ingredientId) {
                                $recetteIngredient->setQuantite($quantite); 
                                break; 
                            }
                        }
                    }
                }
            }
        }
    
        $entityManager->flush();
    
        return $this->json(['status' => 'success', 'message' => 'Recette mise à jour avec succès']);
    }
}

