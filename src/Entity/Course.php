<?php

namespace App\Entity;

use App\Dto\CourseDto;
use App\Repository\CourseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: CourseRepository::class)]
#[UniqueEntity(
    fields: ['code'],
    message: 'Данный код уже используется в другом курсе!',
)]
class Course
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $code;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $type;

    #[ORM\Column(nullable: true)]
    private ?float $price;

    #[ORM\Column]
    private ?string $title;

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

    public static function fromDto(CourseDto $courseDto)
    {
        $course = new self();

        $course
            ->setPrice($courseDto->getPrice())
            ->setType(self::REVERSE_COURSE_TYPES[$courseDto->getType()])
            ->setCode($courseDto->getCode())
            ->setTitle($courseDto->getTitle());

        return $course;
    }

    public function updateFromDto(CourseDto $courseDto)
    {
        $this
            ->setPrice($courseDto->getPrice())
            ->setType(self::REVERSE_COURSE_TYPES[$courseDto->getType()])
            ->setCode($courseDto->getCode())
            ->setTitle($courseDto->getTitle());

        return $this;
    }

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
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

    public function setPrice(?float $price): self
    {
        $this->price = $price;

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
