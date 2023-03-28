<?php

namespace App\Entity;

use App\DTO\CourseDTO;
use App\Repository\CourseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CourseRepository::class)]
class Course
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $code = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $type = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\OneToMany(mappedBy: 'course', targetEntity: Transaction::class)]
    private Collection $transactions;

    private const COURSE_TYPES = [
        1 => 'rent',
        2 => 'free',
        3 => 'buy'
    ];

    private const REVERSE_COURSE_TYPES = [
        'rent' => 1,
        'free' => 2,
        'buy' => 3,
    ];

    public static function fromDTO(CourseDTO $dto)
    {
        $course = new self();

        $course
            ->setPrice($dto->price)
            ->setType(self::REVERSE_COURSE_TYPES[$dto->type])
            ->setCode($dto->code)
            ->setTitle($dto->title);

        return $course;
    }

    public function updateFromDTO(CourseDTO $dto)
    {
        $this
            ->setPrice($dto->price)
            ->setType(self::REVERSE_COURSE_TYPES[$dto->type])
            ->setCode($dto->code)
            ->setTitle($dto->title);

        return $this;
    }

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getType(): ?string
    {
        return self::COURSE_TYPES[$this->type];
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): self
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setCourse($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): self
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getCourse() === $this) {
                $transaction->setCourse(null);
            }
        }

        return $this;
    }
}
