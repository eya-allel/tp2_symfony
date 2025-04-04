<?php
namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(
        min: 5,
        max: 50,
        minMessage: "Le nom d'un article doit comporter au moins {{ limit }} caractères",
        maxMessage: "Le nom d'un article doit comporter au plus {{ limit }} caractères"
    )]
    private ?string $nom = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotEqualTo(
    value: 0,
    message: "Le prix d'un article ne doit pas être égal à 0"
    )]
    private ?int $prix = null;

    // Dans src/Entity/Article.php
   // Dans src/Entity/Article.php
#[ORM\ManyToOne(inversedBy: 'articles')]
#[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
private ?Category $category = null;

    // Getters et setters...


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?String
    {
        return $this->nom;
    }

    public function setNom(String $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrix(): ?int
    {
        return $this->prix;
    }

    public function setPrix(int $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }
}
