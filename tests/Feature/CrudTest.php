<?php

namespace Tests\Feature;

use App\Models\Mahasiswa;
use App\Models\Prodi;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_redirects_to_mahasiswa(): void
    {
        $this->get('/')->assertRedirect('/mahasiswa');
    }

    public function test_empty_lists_and_create_forms_are_available(): void
    {
        $this->get('/prodi')->assertOk()->assertSee('Belum ada data prodi');
        $this->get('/mahasiswa')->assertOk()->assertSee('Belum ada data mahasiswa');
        $this->get('/prodi/create')->assertOk()->assertSee('Nama Prodi');
        $this->get('/mahasiswa/create')->assertOk()->assertSee('Belum ada prodi')->assertSee('disabled', false);
    }

    public function test_prodi_can_be_created_edited_and_deleted(): void
    {
        $this->post('/prodi', ['nama_prodi' => 'Sistem Informasi'])
            ->assertRedirect('/prodi')->assertSessionHas('success');
        $prodi = Prodi::firstOrFail();
        $this->get('/prodi')->assertOk()->assertSee('Sistem Informasi');
        $this->get("/prodi/{$prodi->id}/edit")->assertOk()->assertSee('Sistem Informasi');
        $this->put("/prodi/{$prodi->id}", ['nama_prodi' => 'Informatika'])->assertRedirect('/prodi');
        $this->assertDatabaseHas('prodi', ['id' => $prodi->id, 'nama_prodi' => 'Informatika']);
        $this->get("/prodi/{$prodi->id}/hapus")->assertOk()->assertSee('Ya, Hapus');
        $this->assertDatabaseHas('prodi', ['id' => $prodi->id]);
        $this->delete("/prodi/{$prodi->id}")->assertRedirect('/prodi');
        $this->assertDatabaseMissing('prodi', ['id' => $prodi->id]);
    }

    public function test_prodi_requires_a_name_of_at_most_100_characters(): void
    {
        foreach (['', '   ', str_repeat('a', 101)] as $nama) {
            $this->post('/prodi', ['nama_prodi' => $nama])->assertSessionHasErrors('nama_prodi');
        }
        $this->assertDatabaseCount('prodi', 0);
    }

    public function test_mahasiswa_can_be_created_updated_moved_and_deleted(): void
    {
        $prodi = Prodi::create(['nama_prodi' => 'Sistem Informasi']);
        $otherProdi = Prodi::create(['nama_prodi' => 'Informatika']);
        $this->get('/mahasiswa/create')->assertOk()->assertSee('Sistem Informasi');
        $this->post('/mahasiswa', ['id_prodi' => $prodi->id, 'nama' => 'Andi', 'nim' => '001234'])
            ->assertRedirect('/mahasiswa')->assertSessionHas('success');
        $mahasiswa = Mahasiswa::firstOrFail();
        $this->assertSame('001234', $mahasiswa->nim);
        $this->assertTrue($mahasiswa->prodi->is($prodi));
        $this->assertTrue($prodi->mahasiswa->first()->is($mahasiswa));
        $this->get('/mahasiswa')->assertOk()->assertSee('Andi')->assertSee('001234')->assertSee('Sistem Informasi');
        $this->get("/mahasiswa/{$mahasiswa->id}/edit")->assertOk()->assertSee('001234');
        $this->put("/mahasiswa/{$mahasiswa->id}", [
            'id_prodi' => $otherProdi->id, 'nama' => 'Andi Saputra', 'nim' => '001235',
        ])->assertRedirect('/mahasiswa');
        $this->assertDatabaseHas('mahasiswa', [
            'id' => $mahasiswa->id, 'id_prodi' => $otherProdi->id, 'nama' => 'Andi Saputra', 'nim' => '001235',
        ]);
        $this->get("/mahasiswa/{$mahasiswa->id}/hapus")->assertOk()->assertSee('Andi Saputra')->assertSee('Ya, Hapus');
        $this->assertDatabaseHas('mahasiswa', ['id' => $mahasiswa->id]);
        $this->delete("/mahasiswa/{$mahasiswa->id}")->assertRedirect('/mahasiswa');
        $this->assertDatabaseMissing('mahasiswa', ['id' => $mahasiswa->id]);
    }

    public function test_mahasiswa_fields_are_required_and_old_input_is_preserved(): void
    {
        $this->from('/mahasiswa/create')->post('/mahasiswa', ['nama' => 'Andi'])
            ->assertRedirect('/mahasiswa/create')
            ->assertSessionHasErrors(['id_prodi', 'nim'])
            ->assertSessionHasInput('nama', 'Andi');
        $this->get('/mahasiswa/create')->assertOk()->assertSee('Silakan pilih prodi.')->assertSee('NIM wajib diisi.');
        $this->post('/mahasiswa', [])->assertSessionHasErrors(['id_prodi', 'nama', 'nim']);
        $this->assertDatabaseCount('mahasiswa', 0);
    }

    public function test_invalid_prodi_and_overlong_values_are_rejected(): void
    {
        $this->post('/mahasiswa', [
            'id_prodi' => 99999, 'nama' => str_repeat('a', 101), 'nim' => str_repeat('1', 31),
        ])->assertSessionHasErrors(['id_prodi', 'nama', 'nim']);
        $this->assertDatabaseCount('mahasiswa', 0);
    }

    public function test_duplicate_nim_is_rejected_on_create_and_update(): void
    {
        $first = $this->student();
        $second = Mahasiswa::create(['id_prodi' => $first->id_prodi, 'nama' => 'Siti', 'nim' => '002']);
        $payload = ['id_prodi' => $first->id_prodi, 'nama' => 'Budi', 'nim' => $first->nim];
        $this->post('/mahasiswa', $payload)->assertSessionHasErrors('nim');
        $this->put("/mahasiswa/{$second->id}", $payload)->assertSessionHasErrors('nim');
        $this->assertDatabaseHas('mahasiswa', ['id' => $second->id, 'nim' => '002']);
        $this->assertDatabaseCount('mahasiswa', 2);
    }

    public function test_mahasiswa_can_keep_their_own_nim_on_update(): void
    {
        $mahasiswa = $this->student();
        $this->put("/mahasiswa/{$mahasiswa->id}", [
            'id_prodi' => $mahasiswa->id_prodi, 'nama' => 'Nama Baru', 'nim' => $mahasiswa->nim,
        ])->assertRedirect('/mahasiswa')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('mahasiswa', ['id' => $mahasiswa->id, 'nama' => 'Nama Baru']);
    }

    public function test_prodi_with_mahasiswa_cannot_be_deleted(): void
    {
        $mahasiswa = $this->student();
        $this->get("/prodi/{$mahasiswa->id_prodi}/hapus")
            ->assertOk()->assertSee('Prodi masih memiliki mahasiswa')->assertDontSee('Ya, Hapus');
        $this->delete("/prodi/{$mahasiswa->id_prodi}")->assertRedirect('/prodi')->assertSessionHas('error');
        $this->assertDatabaseHas('prodi', ['id' => $mahasiswa->id_prodi]);
        $this->assertDatabaseHas('mahasiswa', ['id' => $mahasiswa->id]);
        $this->delete("/mahasiswa/{$mahasiswa->id}")->assertRedirect('/mahasiswa');
        $this->delete("/prodi/{$mahasiswa->id_prodi}")->assertRedirect('/prodi');
        $this->assertDatabaseCount('prodi', 0);
    }

    public function test_database_foreign_key_also_prevents_deleting_a_used_prodi(): void
    {
        $mahasiswa = $this->student();
        $this->expectException(QueryException::class);
        $mahasiswa->prodi->delete();
    }

    public function test_database_foreign_key_rejects_an_unknown_prodi(): void
    {
        $this->expectException(QueryException::class);
        Mahasiswa::create(['id_prodi' => 99999, 'nama' => 'Andi', 'nim' => '001']);
    }

    public function test_missing_records_return_404(): void
    {
        foreach (['prodi', 'mahasiswa'] as $resource) {
            $this->get("/{$resource}/99999/edit")->assertNotFound();
            $this->get("/{$resource}/99999/hapus")->assertNotFound();
            $this->put("/{$resource}/99999", [])->assertNotFound();
            $this->delete("/{$resource}/99999")->assertNotFound();
        }
    }

    public function test_both_lists_use_bootstrap_pagination(): void
    {
        $prodi = Prodi::create(['nama_prodi' => 'Sistem Informasi']);
        for ($i = 1; $i <= 11; $i++) {
            Prodi::create(['nama_prodi' => "Prodi {$i}"]);
            Mahasiswa::create(['id_prodi' => $prodi->id, 'nama' => "Mahasiswa {$i}", 'nim' => "NIM{$i}"]);
        }
        $this->get('/prodi')->assertOk()->assertSee('pagination', false)->assertDontSee('Prodi 11');
        $this->get('/prodi?page=2')->assertOk()->assertSee('Prodi 11');
        $this->get('/mahasiswa')->assertOk()->assertSee('pagination', false)->assertDontSee('NIM11');
        $this->get('/mahasiswa?page=2')->assertOk()->assertSee('NIM11');
    }

    public function test_names_are_escaped_in_html(): void
    {
        $name = '<script>alert(1)</script>';
        $prodi = Prodi::create(['nama_prodi' => $name]);
        Mahasiswa::create(['id_prodi' => $prodi->id, 'nama' => $name, 'nim' => '001']);
        $this->get('/prodi')->assertOk()->assertSee($name)->assertDontSee($name, false);
        $this->get('/mahasiswa')->assertOk()->assertSee($name)->assertDontSee($name, false);
    }

    public function test_sample_data_can_be_seeded_repeatedly_without_duplicates(): void
    {
        $this->seed();
        $this->seed();
        $this->assertDatabaseCount('prodi', 2);
        $this->assertDatabaseCount('mahasiswa', 2);
        $this->get('/mahasiswa')->assertOk()->assertSee('0012026001');
    }

    private function student(): Mahasiswa
    {
        $prodi = Prodi::create(['nama_prodi' => 'Sistem Informasi']);

        return Mahasiswa::create(['id_prodi' => $prodi->id, 'nama' => 'Andi', 'nim' => '001']);
    }
}
