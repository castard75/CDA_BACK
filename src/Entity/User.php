<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use App\ApiResource\DTO\UserDTO;
use App\ApiResource\State\UserCreateProcessor;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use ApiPlatform\Metadata\ApiProperty;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['user']],
    operations: [
        new Get(),
        new GetCollection(),
        new Post(uriTemplate: 'create/user', input: UserDto::class, processor: UserCreateProcessor::class),
        new Post(uriTemplate: 'create/image/user',inputFormats: ['multipart' => ['multipart/form-data']],normalizationContext: ['groups' => ['picture:read']],
            denormalizationContext: ['groups' => ['picture:write']]),
        new Post(
            uriTemplate: 'update/image/{id}/user',
            inputFormats: ['multipart' => ['multipart/form-data']],
            normalizationContext: ['groups' => ['picture:read']],
            denormalizationContext: ['groups' => ['picture:write']]
        ),
    ]
)]
#[Vich\Uploadable]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[Groups(['user'])]
    #[ORM\Column]
    private ?int $id = null;
    

    #[Assert\Email(
        message: 'The email {{ value }} is not a valid email.',
    )]
    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Groups(['user'])]
    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    #[Groups(['user'])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */

    #[Assert\PasswordStrength]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Music::class, orphanRemoval: true)]
    private Collection $music;

    #[Assert\Regex(
        pattern: '/\d/',
        match: false,
        message: 'Your name cannot contain a number',
    )]
    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Groups(['user'])]
    #[ORM\Column(length: 155)]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'user_id', targetEntity: Stats::class)]
    private Collection $stats;

    #[Vich\UploadableField(mapping: 'picture', fileNameProperty: 'filename')]
    #[Groups(['picture:write'])]
    private $file;



    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['picture:read', 'user'])]
    private ?string $filename = null;

    #[ApiProperty(types: ['https://schema.org/contentUrl'])]
    #[Groups(['picture:read', 'user'])]
    public ?string $contentUrl = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private $updatedAt;

    public function __construct()
    {
        $this->music = new ArrayCollection();
        $this->stats = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, Music>
     */
    public function getMusic(): Collection
    {
        return $this->music;
    }

    public function addMusic(Music $music): static
    {
        if (!$this->music->contains($music)) {
            $this->music->add($music);
            $music->setUser($this);
        }

        return $this;
    }

    public function removeMusic(Music $music): static
    {
        if ($this->music->removeElement($music)) {
            // set the owning side to null (unless already changed)
            if ($music->getUser() === $this) {
                $music->setUser(null);
            }
        }

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Stats>
     */
    public function getStats(): Collection
    {
        return $this->stats;
    }

    public function addStat(Stats $stat): static
    {
        if (!$this->stats->contains($stat)) {
            $this->stats->add($stat);
            $stat->setUserId($this);
        }

        return $this;
    }

    public function removeStat(Stats $stat): static
    {
        if ($this->stats->removeElement($stat)) {
            // set the owning side to null (unless already changed)
            if ($stat->getUserId() === $this) {
                $stat->setUserId(null);
            }
        }

        return $this;
    }



    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }


    public function getFile(){
        return $this->file;
    }

    public function setFile(?File $file){

        $this->file = $file;
        if ($file) {
            // if 'updatedAt' is not defined in your entity, use another property
            $this->updatedAt = new \DateTime('now');
        }
        return $this;
    }

    public function getContentUrl(): ?string
    {
        return $this->contentUrl;
    }

    public function setContentUrl(?string $contentUrl): void
    {
        $this->contentUrl = $contentUrl;
    }



    /**
     * Sets updatedAt.
     *
     * @return $this
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Returns updatedAt.
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}
