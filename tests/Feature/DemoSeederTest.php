<?php
namespace Tests\Feature;
use App\Models\CafeTable; use App\Models\Sale; use Illuminate\Foundation\Testing\RefreshDatabase; use Tests\TestCase;
class DemoSeederTest extends TestCase { use RefreshDatabase; public function test_demo_seeder_creates_tables_and_ten_sales(): void { $this->seed(\Database\Seeders\DemoDataSeeder::class); $this->assertGreaterThanOrEqual(4, CafeTable::count()); $this->assertGreaterThanOrEqual(10, Sale::count()); } }
