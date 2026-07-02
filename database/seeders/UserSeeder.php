<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Alessandro Moscetta',
                'email' => 'a.moscetta@dunp.it'
            ],
            [
                'name' => 'Angelo Surano',
                'email' => 'a.surano@dunp.it'
            ],
            [
                'name' => 'Chiara Zamponi',
                'email' => 'c.zamponi@dunp.it'
            ],
            [
                'name' => 'Claudio Polito',
                'email' => 'c.polito@dunp.it'
            ],
            [
                'name' => 'Desirèe Iafrate',
                'email' => 'd.iafrate@dunp.it'
            ],
            [
                'name' => 'Edoardo Andreoni',
                'email' => 'e.andreoni@dunp.it'
            ],
            [
                'name' => 'Elena Marù',
                'email' => 'e.maru@dunp.it'
            ],
            [
                'name' => 'Emanuele Longhi',
                'email' => 'e.longhi@dunp.it'
            ],
            [
                'name' => 'Francesca Cutini',
                'email' => 'f.cutini@dunp.it'
            ],
            [
                'name' => 'Gabriele Simonetti',
                'email' => 'g.simonetti@dunp.it'
            ],
            [
                'name' => 'Giulia Bernardini',
                'email' => 'g.bernardini@dunp.it'
            ],
            [
                'name' => 'Giuseppe Pugliese',
                'email' => 'g.pugliese@dunp.it'
            ],
            [
                'name' => 'Laura Di Sante',
                'email' => 'l.disante@dunp.it'
            ],
            [
                'name' => 'Lorenzo Lettieri',
                'email' => 'l.lettieri@dunp.it'
            ],
             [
                'name' => 'Martina Marabitti',
                'email' => 'm.marabitti@dunp.it'
            ],
            [
                'name' => 'Massimiliano De Scisciolo',
                'email' => 'm.descisciolo@dunp.it'
            ],
            [
                'name' => 'Sonia Mariucci',
                'email' => 's.mariucci@dunp.it'
            ],
            [
                'name' => 'Tiziana Evangelisti',
                'email' => 't.evangelisti@dunp.it'
            ],

        ];

        foreach ($users as $user) {
            \App\Models\User::firstOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'is_enabled' => true,
                ]
            );
        }
    }
}
