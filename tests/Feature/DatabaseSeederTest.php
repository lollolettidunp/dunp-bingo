<?php

namespace Tests\Feature;

use App\Models\Cell;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_legacy_cells_idempotently(): void
    {
        $this->seed();
        $count = Cell::count();
        $this->seed();

        $this->assertGreaterThanOrEqual(24, $count);
        $this->assertSame($count, Cell::count());
        $this->assertDatabaseHas('cells', ['text' => 'Giuseppe viene in ufficio infortunato']);
    }
    public function test_cells_text_column_is_indexable_on_mysql(): void
    {
        $column = collect(Schema::getColumns('cells'))->firstWhere('name', 'text');

        $this->assertSame('varchar', $column['type_name']);
    }
}
