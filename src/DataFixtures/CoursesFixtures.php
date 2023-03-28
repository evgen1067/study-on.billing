<?php

namespace App\DataFixtures;

use App\Entity\Course;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CoursesFixtures extends Fixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $courses = [
            [
                'code' => 'PHP-1',
                'type' => 1,
                'title' => 'Ключевые аспекты веб-разработки на PHP',
                'price' => 1000,
            ],
            [
                'code' => 'JS-1',
                'type' => 1,
                'title' => 'Основы JavaScript',
                'price' => 2000,
            ],
            [
                'code' => 'HTML-1',
                'type' => 2,
                'title' => 'Основы современной верстки',
                'price' => 0,
            ],
            [
                'code' => 'GIT-1',
                'type' => 3,
                'title' => 'Введение в Git',
                'price' => 2000,
            ],
            [
                'code' => 'OS-1',
                'type' => 3,
                'title' => 'Операционные системы',
                'price' => 1000,
            ],
        ];

        foreach ($courses as $course) {
            $fixtureCourse = new Course();
            $fixtureCourse
                ->setCode($course['code'])
                ->setType($course['type'])
                ->setPrice($course['price'])
                ->setTitle($course['title']);
            $manager->persist($fixtureCourse);
        }
        $manager->flush();
    }

    public function getOrder(): int
    {
        return 1; // smaller means sooner
    }
}