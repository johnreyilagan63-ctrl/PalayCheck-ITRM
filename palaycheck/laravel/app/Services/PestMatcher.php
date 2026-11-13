<?php

namespace App\Services;

use App\Models\Pest;

class PestMatcher
{
    public const ALIASES = [
        'wereng' => 'Brown Planthopper',
        'wereng coklat' => 'Brown Planthopper',
        'wereng batang coklat' => 'Brown Planthopper',
        'brown plant hopper' => 'Brown Planthopper',
        'brown planthopper' => 'Brown Planthopper',
        'planthopper' => 'Brown Planthopper',
        'plant hopper' => 'Brown Planthopper',
        'small brown plant hopper' => 'Small Brown Planthopper',
        'small brown planthopper' => 'Small Brown Planthopper',
        'leaf hopper' => 'Green Leafhopper',
        'leafhopper' => 'Green Leafhopper',
        'green leaf hopper' => 'Green Leafhopper',
        'rice leaf hopper' => 'Green Leafhopper',
        'wereng hijau' => 'Green Leafhopper',
        'stem borer' => 'Yellow Rice Stem Borer',
        'rice stem borer' => 'Yellow Rice Stem Borer',
        'yellow stem borer' => 'Yellow Rice Stem Borer',
        'yellow rice borer' => 'Yellow Rice Stem Borer',
        'yellow rice stem borer' => 'Yellow Rice Stem Borer',
        'penggerek batang' => 'Yellow Rice Stem Borer',
        'penggerek batang padi' => 'Yellow Rice Stem Borer',
        'asiatic rice borer' => 'Asiatic Rice Borer',
        'adult rice borer' => 'Asiatic Rice Borer',
        'rice leaf folder' => 'Rice Leaffolder',
        'rice leaf roller' => 'Rice Leaffolder',
        'ulat lipat' => 'Rice Leaffolder',
        'ulat pelipat daun' => 'Rice Leaffolder',
        'rice bug' => 'Rice Bug',
        'walang sangit' => 'Rice Bug',
        'water weevil' => 'Rice Water Weevil',
        'rice water weevil' => 'Rice Water Weevil',
        'weevil' => 'Rice Water Weevil',
        'rice gall midge' => 'Rice Gall Midge',
        'gall midge' => 'Rice Gall Midge',
        'whorl maggot' => 'Rice Whorl Maggot',
        'rice whorl maggot' => 'Rice Whorl Maggot',
        'paddy stem maggot' => 'Paddy Stem Maggot',
        'army worm' => 'Rice Armyworm',
        'armyworm' => 'Rice Armyworm',
        'rice armyworm' => 'Rice Armyworm',
        'cut worm' => 'Cutworm',
        'cutworm' => 'Cutworm',
        'rice leaf caterpillar' => 'Rice Leaf Caterpillar',
        'caterpillar' => 'Rice Leaf Caterpillar',
        'ulat' => 'Rice Leaf Caterpillar',
    ];

    public static function normalize(?string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', strtolower((string) $value)));
    }

    public function match(string $label): ?Pest
    {
        $normalized = self::normalize($label);
        if ($normalized === '') {
            return null;
        }

        $normalized = self::normalize(self::ALIASES[$normalized] ?? $normalized);
        $pests = Pest::all();

        foreach ($pests as $pest) {
            if (self::normalize($pest->name) === $normalized) {
                return $pest;
            }
        }

        foreach ($pests as $pest) {
            $name = self::normalize($pest->name);
            if ($name !== '' && (str_contains($normalized, $name) || str_contains($name, $normalized))) {
                return $pest;
            }
        }

        return null;
    }
}
