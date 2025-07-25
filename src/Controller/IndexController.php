<?php
namespace App\Controller;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\PropertySearch;
use App\Entity\CategorySearch;
use App\Entity\PriceSearch;
use App\Form\PropertySearchType;
use App\Form\CategorySearchType;
use App\Form\PriceSearchType;
use App\Form\Article1Type;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class IndexController extends AbstractController {
    #[Route('/', name: 'article_list')]
    public function home(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Création du formulaire de recherche par nom
        $propertySearch = new PropertySearch();
        $form = $this->createForm(PropertySearchType::class, $propertySearch);
        $form->handleRequest($request);
        
        // Par défaut, récupérer tous les articles (modification ici)
        $articles = $entityManager->getRepository(Article::class)->findAll();
        
        if ($form->isSubmitted() && $form->isValid()) {
            // On récupère le nom d'article tapé dans le formulaire
            $nom = $propertySearch->getNom();
            
            if ($nom != "") {
                // Si on a fourni un nom d'article on affiche tous les articles ayant ce nom
                $articles = $entityManager->getRepository(Article::class)->findBy(['nom' => $nom]);
            }
            // Pas besoin du else ici, car nous avons déjà chargé tous les articles par défaut
        }
        
        // Filtrer les articles avec des catégories problématiques
        $validArticles = [];
        foreach ($articles as $article) {
            try {
                // Tenter d'accéder à la catégorie pour vérifier si elle est valide
                if ($article->getCategory() === null) {
                    // Article sans catégorie, on l'ajoute quand même
                    $validArticles[] = $article;
                } else {
                    // On essaie d'accéder à l'ID pour voir si la catégorie est valide
                    $categoryId = $article->getCategory()->getId();
                    if ($categoryId > 0) {
                        $validArticles[] = $article;
                    }
                }
            } catch (\Exception $e) {
                // Si une erreur se produit, on ignore cet article
                continue;
            }
        }
        
        return $this->render('articles/index.html.twig', [
            'form' => $form->createView(),
            'articles' => $validArticles
        ]);
    }

    #[Route('/article/save', name: 'article_save')]
    public function save(EntityManagerInterface $entityManager): Response
    {
        $article = new Article();
        $article->setNom('Article 1');
        $article->setPrix(1000);
        
        $entityManager->persist($article);
        $entityManager->flush();
        
        return new Response('Article enregistré avec id '.$article->getId());
    }

    #[Route('/article/new', name: 'new_article')] 
    public function new(Request $request, EntityManagerInterface $entityManager): Response {
        $article = new Article();
        $form = $this->createForm(Article1Type::class, $article);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($article);
            $entityManager->flush();
            
            return $this->redirectToRoute('article_list');
        }
        
        return $this->render('articles/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/article/{id}', name: 'article_show')]
    public function show(EntityManagerInterface $entityManager, int $id): Response
    {
        $article = $entityManager->getRepository(Article::class)->find($id);
        
        if (!$article) {
            throw $this->createNotFoundException('Article non trouvé');
        }
        
        return $this->render('articles/show.html.twig', [
            'article' => $article
        ]);
    }

    #[Route('/article/edit/{id}', name: 'edit_article')] 
    public function edit(Request $request, EntityManagerInterface $entityManager, int $id): Response {
        $article = $entityManager->getRepository(Article::class)->find($id);
        
        if (!$article) {
            throw $this->createNotFoundException('Article non trouvé');
        }
        
        $form = $this->createForm(Article1Type::class, $article);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            return $this->redirectToRoute('article_list');
        }
        
        return $this->render('articles/edit.html.twig', [
            'form' => $form->createView()
        ]);
    }
 
    #[Route('/article/delete/{id}', name: 'delete_article')]
    public function delete(EntityManagerInterface $entityManager, int $id): Response
    {
        $article = $entityManager->getRepository(Article::class)->find($id);
        
        if (!$article) {
            throw $this->createNotFoundException('Article non trouvé');
        }
        
        $entityManager->remove($article);
        $entityManager->flush();
        
        return $this->redirectToRoute('article_list');
    }

    #[Route('/maintenance/fix-categories', name: 'fix_categories')]
    public function fixCategories(EntityManagerInterface $entityManager): Response
    {
        // Récupérer tous les articles
        $articles = $entityManager->getRepository(Article::class)->findAll();
        $categoryRepo = $entityManager->getRepository(Category::class);
        $fixedCount = 0;
        
        foreach ($articles as $article) {
            $category = $article->getCategory();
            
            // Vérifier si la catégorie existe toujours
            if ($category !== null) {
                try {
                    // Essayer de récupérer la catégorie depuis la base de données
                    $categoryExists = $categoryRepo->find($category->getId());
                    
                    if (!$categoryExists) {
                        // La catégorie n'existe plus, mettre la référence à null
                        $article->setCategory(null);
                        $fixedCount++;
                    }
                } catch (\Exception $e) {
                    // En cas d'erreur, mettre la catégorie à null
                    $article->setCategory(null);
                    $fixedCount++;
                }
            }
        }
        
        // Sauvegarder les changements
        $entityManager->flush();
        
        return new Response("Réparation terminée. $fixedCount articles ont été mis à jour.");
    }

    #[Route('/maintenance/restore-categories', name: 'restore_categories')]
    public function restoreCategories(EntityManagerInterface $entityManager): Response
    {
        // Exemple : recréer la catégorie avec ID 1 si elle n'existe pas
        $category = $entityManager->getRepository(Category::class)->find(1);
        
        if (!$category) {
            $category = new Category();
            // Utiliser setId uniquement si votre entité le permet, sinon adaptez votre stratégie
            // $category->setId(1); 
            $category->setTitre('Catégorie restaurée');
            $category->setDescription('Cette catégorie a été restaurée automatiquement');
            
            $entityManager->persist($category);
            $entityManager->flush();
            
            return new Response('Catégorie restaurée avec succès');
        }
        
        return new Response('Aucune action nécessaire');
    }

    /**
     * @Route("/art_cat/", name="article_par_cat")
     */
    #[Route('/art_cat/', name: 'article_par_cat')]
    public function articlesParCategorie(Request $request, EntityManagerInterface $entityManager): Response
    {
        $categorySearch = new CategorySearch();
        $form = $this->createForm(CategorySearchType::class, $categorySearch);
        $form->handleRequest($request);
        
        $articles = [];
        
        if ($form->isSubmitted() && $form->isValid()) {
            $category = $categorySearch->getCategory();
            
            if ($category) {
                $articles = $category->getArticles();
            } else {
                $articles = $entityManager->getRepository(Article::class)->findAll();
            }
        }
        
        return $this->render('articles/articlesParCategorie.html.twig', [
            'form' => $form->createView(),
            'articles' => $articles
        ]);
    }

    /**
     * @Route("/art_prix/", name="article_par_prix")
     */
    #[Route('/art_prix/', name: 'article_par_prix')]
    public function articlesParPrix(Request $request, EntityManagerInterface $entityManager): Response
    {
        $priceSearch = new PriceSearch();
        $form = $this->createForm(PriceSearchType::class, $priceSearch);
        $form->handleRequest($request);
        
        $articles = [];
        
        if ($form->isSubmitted() && $form->isValid()) {
            $minPrice = $priceSearch->getMinPrice();
            $maxPrice = $priceSearch->getMaxPrice();
            
            $articles = $entityManager->getRepository(Article::class)
                ->findByPriceRange($minPrice, $maxPrice);
        }
        
        return $this->render('articles/articlesParPrix.html.twig', [
            'form' => $form->createView(),
            'articles' => $articles
        ]);
    }
    #[Route('/category/new', name: 'new_category')]
    public function newCategory(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $form = $this->createForm(\App\Form\CategoryType::class, $category);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();
            
            $this->addFlash('success', 'Catégorie créée avec succès');
            return $this->redirectToRoute('category_index');
        }
        
        return $this->render('category/new.html.twig', [
            'form' => $form->createView()
        ]);
    }
    #[Route('/category', name: 'category_index')]
public function categoryIndex(EntityManagerInterface $entityManager): Response
{
    $categories = $entityManager->getRepository(Category::class)->findAll();
    
    return $this->render('category/index.html.twig', [
        'categories' => $categories
    ]);
}
#[Route('/category/{id}', name: 'category_show')]
public function categoryShow(EntityManagerInterface $entityManager, int $id): Response
{
    $category = $entityManager->getRepository(Category::class)->find($id);
    
    if (!$category) {
        throw $this->createNotFoundException('Catégorie non trouvée');
    }
    
    return $this->render('category/show.html.twig', [
        'category' => $category
    ]);
}
#[Route('/category/edit/{id}', name: 'category_edit')]
public function categoryEdit(Request $request, EntityManagerInterface $entityManager, int $id): Response
{
    $category = $entityManager->getRepository(Category::class)->find($id);
    
    if (!$category) {
        throw $this->createNotFoundException('Catégorie non trouvée');
    }
    
    $form = $this->createForm(\App\Form\CategoryType::class, $category);
    $form->handleRequest($request);
    
    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();
        
        $this->addFlash('success', 'Catégorie modifiée avec succès');
        return $this->redirectToRoute('category_index');
    }
    
    return $this->render('category/edit.html.twig', [
        'form' => $form->createView(),
        'category' => $category
    ]);
}

#[Route('/category/delete/{id}', name: 'category_delete')]
public function categoryDelete(Request $request, EntityManagerInterface $entityManager, int $id): Response
{
    $category = $entityManager->getRepository(Category::class)->find($id);
    
    if (!$category) {
        throw $this->createNotFoundException('Catégorie non trouvée');
    }
    
    // Vérification du token CSRF
    $token = $request->request->get('_token');
    if ($this->isCsrfTokenValid('delete'.$id, $token)) {
        $entityManager->remove($category);
        $entityManager->flush();
        
        $this->addFlash('success', 'Catégorie supprimée avec succès');
    } else {
        $this->addFlash('error', 'Token CSRF invalide');
    }
    
    return $this->redirectToRoute('category_index');
}

}