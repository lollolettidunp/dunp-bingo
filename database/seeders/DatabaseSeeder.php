<?php

namespace Database\Seeders;

use App\Models\Cell;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $data = json_decode(file_get_contents(database_path('data/bingo.json')), true, flags: JSON_THROW_ON_ERROR);

        foreach ($data['squares'] as $square) {
            $square = is_string($square) ? ['text' => $square] : $square;

            Cell::firstOrCreate(
                ['text' => trim($square['text'])],
                [
                    'difficulty' => 2,
                    'is_active' => true,
                    'excluded_weekdays' => array_values(array_map(
                        fn (string $day) => Str::ascii(strtolower($day)),
                        array_filter((array) ($square['except'] ?? [])),
                    )),
                ],
            );
        }
    }
}
