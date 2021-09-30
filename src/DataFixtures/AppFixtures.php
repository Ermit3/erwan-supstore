<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Product;
use App\Entity\Category;
use Bezhanov\Faker\Provider\Commerce;
use Bluemmb\Faker\PicsumPhotosProvider;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\String\Slugger\SluggerInterface;

class AppFixtures extends Fixture
{
    protected $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create('fr_FR');
        $faker->addProvider(new \Bezhanov\Faker\Provider\Commerce($faker));
        $faker->addProvider(new \Bluemmb\Faker\PicsumPhotosProvider($faker));

        $products = [];
        $category_list = ['Nouvelle Collection', 'Homme', 'Femme'];

        for ($c = 0; $c < 3; $c++) {
            $category = new Category;

            $category->setName($category_list[$c])
                ->setSlug(strtolower($this->slugger->slug($category->getName())));

            $manager->persist($category);

            for ($p = 0; $p < mt_rand(15, 20); $p++) {
                $product = new Product;

                $product->setName($faker->productName())
                    ->setPrice($faker->randomFloat($nbMaxDecimals = 2, $min = 20, $max = 500))
                    ->setSlug(strtolower($this->slugger->slug($product->getName())))
                    ->setCategory($category)
                    ->setDescription($faker->paragraph())
                    ->setMainPicture($faker->imageUrl(800, 800, true));

                $products[] = $product;

                $manager->persist($product);
            }
        }

        $manager->flush();
    }
}
