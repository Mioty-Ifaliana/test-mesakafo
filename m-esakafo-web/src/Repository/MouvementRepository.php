<?php

namespace App\Repository;

use App\Entity\Mouvement;
use App\Entity\Ingredient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Mouvement>
 *
 * @method Mouvement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Mouvement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Mouvement[]    findAll()
 * @method Mouvement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MouvementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mouvement::class);
    }

    public function save(Mouvement $mouvement): void
    {
        $this->getEntityManager()->persist($mouvement);
        $this->getEntityManager()->flush();
    }

    public function findByIngredientId(int $ingredientId): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.ingredient = :ingredientId')
            ->setParameter('ingredientId', $ingredientId)
            ->orderBy('m.dateMouvement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getStockActuel(int $ingredientId): int
    {
        $result = $this->createQueryBuilder('m')
            ->select('SUM(m.entree) as total_entrees, SUM(m.sortie) as total_sorties')
            ->andWhere('m.ingredient = :ingredientId')
            ->setParameter('ingredientId', $ingredientId)
            ->getQuery()
            ->getOneOrNullResult();

        $totalEntrees = $result['total_entrees'] ?? 0;
        $totalSorties = $result['total_sorties'] ?? 0;

        return $totalEntrees - $totalSorties;
    }

    public function getAllStocks(): array
    {
        $em = $this->getEntityManager();
        
        // Récupérer tous les ingrédients
        $ingredients = $em->getRepository(Ingredient::class)->findAll();
        
        $stocks = [];
        foreach ($ingredients as $ingredient) {
            $result = $this->createQueryBuilder('m')
                ->select('SUM(m.entree) as total_entrees, SUM(m.sortie) as total_sorties')
                ->andWhere('m.ingredient = :ingredient')
                ->setParameter('ingredient', $ingredient)
                ->getQuery()
                ->getOneOrNullResult();

            $totalEntrees = $result['total_entrees'] ?? 0;
            $totalSorties = $result['total_sorties'] ?? 0;
            $stockActuel = $totalEntrees - $totalSorties;

            $stocks[] = [
                'ingredient' => $ingredient,
                'stock_actuel' => $stockActuel,
                'total_entrees' => $totalEntrees,
                'total_sorties' => $totalSorties
            ];
        }
        
        return $stocks;
    }

    public function findByDateRange(\DateTime $debut, \DateTime $fin): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.dateMouvement BETWEEN :debut AND :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->orderBy('m.dateMouvement', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllMouvements(): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.ingredient', 'i')
            ->addSelect('i')
            ->orderBy('m.dateMouvement', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
