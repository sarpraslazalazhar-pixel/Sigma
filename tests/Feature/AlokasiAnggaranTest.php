<?php

namespace Tests\Feature;

use App\Livewire\Components\YearSwitcher;
use App\Livewire\Master\KodeAnggaranIndex;
use App\Livewire\Master\TahunAnggaranIndex;
use App\Livewire\Permohonan\PermohonanCreate;
use App\Models\AlokasiAnggaran;
use App\Models\AuditLog;
use App\Models\KodeAnggaran;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\Unit;
use App\Models\User;
use App\Services\BudgetCalculator;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AlokasiAnggaranTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $staffUser;

    protected TahunAnggaran $taAktif;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            MasterDataSeeder::class,
            UserSeeder::class,
        ]);

        $this->adminUser = User::where('role', 'admin')->firstOrFail();
        $this->staffUser = User::where('username', 'subakti')->firstOrFail();
        $this->taAktif = TahunAnggaran::where('status', 'aktif')->firstOrFail();
    }

    public function test_alokasi_anggaran_created_and_read_by_budget_calculator(): void
    {
        $node = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->taAktif->id,
            'kode' => '9.1',
            'nama' => 'Pos Uji Alokasi',
            'depth' => 1,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        AlokasiAnggaran::create([
            'kode_anggaran_id' => $node->id,
            'tahun_anggaran_id' => $this->taAktif->id,
            'plafon_awal' => 75000000,
        ]);

        $calculator = new BudgetCalculator;
        $tree = $calculator->calculateTree($this->taAktif->id);

        $this->assertTrue($tree->has($node->id));
        $this->assertSame(75000000, $tree->get($node->id)['plafon']);
        $this->assertSame(75000000, $tree->get($node->id)['effective_budget']);
        $this->assertSame(75000000, $node->getPlafonEfektif());
    }

    public function test_plafon_revisi_overrides_plafon_awal(): void
    {
        $node = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->taAktif->id,
            'kode' => '9.2',
            'nama' => 'Pos Revisi Override',
            'depth' => 1,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $alokasi = AlokasiAnggaran::create([
            'kode_anggaran_id' => $node->id,
            'tahun_anggaran_id' => $this->taAktif->id,
            'plafon_awal' => 50000000,
            'plafon_revisi' => 65000000,
            'keterangan_revisi' => 'Revisi ABT pertengahan tahun',
        ]);

        $this->assertSame(65000000, $alokasi->plafon_efektif);
        $this->assertSame(65000000, $node->getPlafonEfektif());

        $calc = (new BudgetCalculator)->calculateTree($this->taAktif->id)->get($node->id);
        $this->assertSame(65000000, $calc['plafon']);
        $this->assertSame(50000000, $calc['plafon_awal']);
        $this->assertSame(65000000, $calc['plafon_revisi']);
    }

    public function test_plafon_awal_used_when_revisi_is_null(): void
    {
        $node = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->taAktif->id,
            'kode' => '9.3',
            'nama' => 'Pos Plafon Awal',
            'depth' => 1,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $alokasi = AlokasiAnggaran::create([
            'kode_anggaran_id' => $node->id,
            'tahun_anggaran_id' => $this->taAktif->id,
            'plafon_awal' => 40000000,
            'plafon_revisi' => null,
        ]);

        $this->assertSame(40000000, $alokasi->plafon_efektif);
        $this->assertSame(40000000, $node->getPlafonEfektif());
    }

    public function test_batch_update_plafon_awal_on_draft_year(): void
    {
        $draftYear = TahunAnggaran::create(['tahun' => 2027, 'status' => 'draft']);

        $node1 = KodeAnggaran::create([
            'tahun_anggaran_id' => $draftYear->id,
            'kode' => '1.1.1',
            'nama' => 'Draft Pos 1',
            'depth' => 3,
            'budget_status' => 'draft',
            'is_active' => true,
        ]);

        $node2 = KodeAnggaran::create([
            'tahun_anggaran_id' => $draftYear->id,
            'kode' => '1.1.2',
            'nama' => 'Draft Pos 2',
            'depth' => 3,
            'budget_status' => 'draft',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(KodeAnggaranIndex::class)
            ->set('tahun_anggaran_id', $draftYear->id)
            ->set('batchEditMode', true)
            ->set('batchEdits', [
                $node1->id => 12000000,
                $node2->id => 18000000,
            ])
            ->call('saveBatchEdits')
            ->assertHasNoErrors();

        $alokasi1 = AlokasiAnggaran::where('kode_anggaran_id', $node1->id)->where('tahun_anggaran_id', $draftYear->id)->first();
        $this->assertNotNull($alokasi1);
        $this->assertSame(12000000, $alokasi1->plafon_awal);
        $this->assertNull($alokasi1->plafon_revisi);

        $alokasi2 = AlokasiAnggaran::where('kode_anggaran_id', $node2->id)->where('tahun_anggaran_id', $draftYear->id)->first();
        $this->assertNotNull($alokasi2);
        $this->assertSame(18000000, $alokasi2->plafon_awal);
    }

    public function test_batch_update_plafon_revisi_on_aktif_year_requires_note(): void
    {
        $node = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->taAktif->id,
            'kode' => '9.4',
            'nama' => 'Pos Aktif Batch',
            'depth' => 1,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        AlokasiAnggaran::create([
            'kode_anggaran_id' => $node->id,
            'tahun_anggaran_id' => $this->taAktif->id,
            'plafon_awal' => 10000000,
        ]);

        // Tanpa catatan revisi pada tahun aktif -> error
        Livewire::actingAs($this->adminUser)
            ->test(KodeAnggaranIndex::class)
            ->set('tahun_anggaran_id', $this->taAktif->id)
            ->set('batchEditMode', true)
            ->set('batchEdits', [$node->id => 15000000])
            ->set('batchRevisionNote', '')
            ->call('saveBatchEdits')
            ->assertHasErrors(['batchRevisionNote']);

        // Dengan catatan revisi -> berhasil dan menulis ke plafon_revisi
        Livewire::actingAs($this->adminUser)
            ->test(KodeAnggaranIndex::class)
            ->set('tahun_anggaran_id', $this->taAktif->id)
            ->set('batchEditMode', true)
            ->set('batchEdits', [$node->id => 15000000])
            ->set('batchRevisionNote', 'Penyesuaian kebutuhan kuartal 3')
            ->call('saveBatchEdits')
            ->assertHasNoErrors();

        $alokasi = AlokasiAnggaran::where('kode_anggaran_id', $node->id)->where('tahun_anggaran_id', $this->taAktif->id)->first();
        $this->assertNotNull($alokasi);
        $this->assertSame(15000000, $alokasi->plafon_revisi);
        $this->assertSame('Penyesuaian kebutuhan kuartal 3', $alokasi->keterangan_revisi);
        $this->assertSame(10000000, $alokasi->plafon_awal);
    }

    public function test_batch_update_rejected_on_closed_year(): void
    {
        $closedYear = TahunAnggaran::create(['tahun' => 2025, 'status' => 'ditutup']);

        $node = KodeAnggaran::create([
            'tahun_anggaran_id' => $closedYear->id,
            'kode' => '9.5',
            'nama' => 'Pos Ditutup Batch',
            'depth' => 1,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(KodeAnggaranIndex::class)
            ->set('tahun_anggaran_id', $closedYear->id)
            ->set('batchEditMode', true)
            ->set('batchEdits', [$node->id => 10000000])
            ->call('saveBatchEdits')
            ->assertHasErrors(['batchRevisionNote']);
    }

    public function test_permohonan_rejected_on_draft_year(): void
    {
        $draftYear = TahunAnggaran::create(['tahun' => 2028, 'status' => 'draft']);

        $leaf = KodeAnggaran::create([
            'tahun_anggaran_id' => $draftYear->id,
            'kode' => '9.6',
            'nama' => 'Leaf Pos Draft Year',
            'depth' => 1,
            'budget_status' => 'draft',
            'is_active' => true,
        ]);

        $sumberDana = SumberDana::where('is_active', true)->firstOrFail();
        $unit = Unit::firstOrFail();

        Livewire::actingAs($this->staffUser)
            ->test(PermohonanCreate::class)
            ->set('unit_id', $unit->id)
            ->set('kode_anggaran_id', $leaf->id)
            ->set('program', 'Program Draft')
            ->set('nama_kegiatan', 'Kegiatan Draft')
            ->set('tgl_mulai', now()->toDateString())
            ->set('sumber_dana_id', $sumberDana->id)
            ->set('items', [
                [
                    'uraian' => 'Uraian pengeluaran',
                    'asnaf_id' => null,
                    'program_utama_id' => null,
                    'jumlah_diminta' => 5000000,
                ],
            ])
            ->call('submitNow')
            ->assertHasErrors(['kode_anggaran_id']);
    }

    public function test_rollover_zero_based(): void
    {
        $targetYear = TahunAnggaran::create(['tahun' => 2029, 'status' => 'draft']);

        $sourceNode = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->taAktif->id,
            'kode' => '9.7',
            'nama' => 'Pos Rollover Zero',
            'depth' => 1,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        AlokasiAnggaran::create([
            'kode_anggaran_id' => $sourceNode->id,
            'tahun_anggaran_id' => $this->taAktif->id,
            'plafon_awal' => 50000000,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(TahunAnggaranIndex::class)
            ->set('rolloverSourceId', $this->taAktif->id)
            ->set('rolloverTargetId', $targetYear->id)
            ->set('rolloverScheme', 'zero')
            ->call('generateRolloverPreview')
            ->assertSet('rolloverStep', 2)
            ->call('executeRollover')
            ->assertHasNoErrors();

        $targetNode = KodeAnggaran::where('tahun_anggaran_id', $targetYear->id)->where('kode', '9.7')->first();
        $this->assertNotNull($targetNode);

        $targetAlokasi = AlokasiAnggaran::where('kode_anggaran_id', $targetNode->id)->where('tahun_anggaran_id', $targetYear->id)->first();
        $this->assertNotNull($targetAlokasi);
        $this->assertSame(0, $targetAlokasi->plafon_awal);
    }

    public function test_rollover_full_copy(): void
    {
        $targetYear = TahunAnggaran::create(['tahun' => 2030, 'status' => 'draft']);

        $sourceNode = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->taAktif->id,
            'kode' => '9.8',
            'nama' => 'Pos Rollover Full',
            'depth' => 1,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        AlokasiAnggaran::create([
            'kode_anggaran_id' => $sourceNode->id,
            'tahun_anggaran_id' => $this->taAktif->id,
            'plafon_awal' => 75000000,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(TahunAnggaranIndex::class)
            ->set('rolloverSourceId', $this->taAktif->id)
            ->set('rolloverTargetId', $targetYear->id)
            ->set('rolloverScheme', 'full')
            ->call('generateRolloverPreview')
            ->assertSet('rolloverStep', 2)
            ->call('executeRollover')
            ->assertHasNoErrors();

        $targetNode = KodeAnggaran::where('tahun_anggaran_id', $targetYear->id)->where('kode', '9.8')->first();
        $this->assertNotNull($targetNode);

        $targetAlokasi = AlokasiAnggaran::where('kode_anggaran_id', $targetNode->id)->where('tahun_anggaran_id', $targetYear->id)->first();
        $this->assertNotNull($targetAlokasi);
        $this->assertSame(75000000, $targetAlokasi->plafon_awal);
    }

    public function test_rollover_percentage_adjustment(): void
    {
        $targetYear = TahunAnggaran::create(['tahun' => 2031, 'status' => 'draft']);

        $sourceNode = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->taAktif->id,
            'kode' => '9.9',
            'nama' => 'Pos Rollover Pct',
            'depth' => 1,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        AlokasiAnggaran::create([
            'kode_anggaran_id' => $sourceNode->id,
            'tahun_anggaran_id' => $this->taAktif->id,
            'plafon_awal' => 100000000,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(TahunAnggaranIndex::class)
            ->set('rolloverSourceId', $this->taAktif->id)
            ->set('rolloverTargetId', $targetYear->id)
            ->set('rolloverScheme', 'percentage')
            ->set('rolloverPercentage', '10') // +10%
            ->call('generateRolloverPreview')
            ->assertSet('rolloverStep', 2)
            ->call('executeRollover')
            ->assertHasNoErrors();

        $targetNode = KodeAnggaran::where('tahun_anggaran_id', $targetYear->id)->where('kode', '9.9')->first();
        $this->assertNotNull($targetNode);

        $targetAlokasi = AlokasiAnggaran::where('kode_anggaran_id', $targetNode->id)->where('tahun_anggaran_id', $targetYear->id)->first();
        $this->assertNotNull($targetAlokasi);
        $this->assertSame(110000000, $targetAlokasi->plafon_awal);
    }

    public function test_rollover_preview_allows_per_item_override(): void
    {
        $targetYear = TahunAnggaran::create(['tahun' => 2035, 'status' => 'draft']);

        $sourceNode1 = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->taAktif->id,
            'kode' => '9.20.1',
            'nama' => 'Pos Leaf 1',
            'depth' => 2,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        $sourceNode2 = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->taAktif->id,
            'kode' => '9.20.2',
            'nama' => 'Pos Leaf 2',
            'depth' => 2,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        AlokasiAnggaran::create([
            'kode_anggaran_id' => $sourceNode1->id,
            'tahun_anggaran_id' => $this->taAktif->id,
            'plafon_awal' => 50000000,
        ]);

        AlokasiAnggaran::create([
            'kode_anggaran_id' => $sourceNode2->id,
            'tahun_anggaran_id' => $this->taAktif->id,
            'plafon_awal' => 100000000,
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(TahunAnggaranIndex::class)
            ->set('rolloverSourceId', $this->taAktif->id)
            ->set('rolloverTargetId', $targetYear->id)
            ->set('rolloverScheme', 'full')
            ->call('generateRolloverPreview')
            ->assertHasNoErrors()
            ->assertSet('rolloverStep', 2);

        $preview = $component->get('rolloverPreview');
        $idx1 = collect($preview)->search(fn ($p) => $p['node_id'] === $sourceNode1->id);
        $this->assertNotFalse($idx1);

        $preview[$idx1]['target_plafon'] = 60000000;

        $component->set('rolloverPreview', $preview)
            ->call('executeRollover')
            ->assertHasNoErrors()
            ->assertSet('rolloverStep', 1)
            ->assertSet('rolloverPreview', []);

        $targetNode1 = KodeAnggaran::where('tahun_anggaran_id', $targetYear->id)->where('kode', '9.20.1')->first();
        $targetNode2 = KodeAnggaran::where('tahun_anggaran_id', $targetYear->id)->where('kode', '9.20.2')->first();

        $this->assertNotNull($targetNode1);
        $this->assertNotNull($targetNode2);

        $alokasi1 = AlokasiAnggaran::where('kode_anggaran_id', $targetNode1->id)->where('tahun_anggaran_id', $targetYear->id)->first();
        $alokasi2 = AlokasiAnggaran::where('kode_anggaran_id', $targetNode2->id)->where('tahun_anggaran_id', $targetYear->id)->first();

        $this->assertNotNull($alokasi1);
        $this->assertNotNull($alokasi2);
        $this->assertSame(60000000, $alokasi1->plafon_awal);
        $this->assertSame(100000000, $alokasi2->plafon_awal);
    }

    public function test_rollover_wizard_navigation_and_validation(): void
    {
        $draftYear = TahunAnggaran::create(['tahun' => 2036, 'status' => 'draft']);
        $aktifYear = TahunAnggaran::create(['tahun' => 2037, 'status' => 'aktif']);

        $leaf = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->taAktif->id,
            'kode' => '9.30.1',
            'nama' => 'Pos Leaf Nav',
            'depth' => 2,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        AlokasiAnggaran::create([
            'kode_anggaran_id' => $leaf->id,
            'tahun_anggaran_id' => $this->taAktif->id,
            'plafon_awal' => 45000000,
        ]);

        // Target must be different from source
        Livewire::actingAs($this->adminUser)
            ->test(TahunAnggaranIndex::class)
            ->set('rolloverSourceId', $this->taAktif->id)
            ->set('rolloverTargetId', $this->taAktif->id)
            ->set('rolloverScheme', 'zero')
            ->call('generateRolloverPreview')
            ->assertHasErrors(['rolloverTargetId']);

        // Target cannot be non-draft
        Livewire::actingAs($this->adminUser)
            ->test(TahunAnggaranIndex::class)
            ->set('rolloverSourceId', $this->taAktif->id)
            ->set('rolloverTargetId', $aktifYear->id)
            ->set('rolloverScheme', 'zero')
            ->call('generateRolloverPreview')
            ->assertHasErrors(['rolloverTargetId']);

        // Percentage scheme requires percentage
        Livewire::actingAs($this->adminUser)
            ->test(TahunAnggaranIndex::class)
            ->set('rolloverSourceId', $this->taAktif->id)
            ->set('rolloverTargetId', $draftYear->id)
            ->set('rolloverScheme', 'percentage')
            ->set('rolloverPercentage', '')
            ->call('generateRolloverPreview')
            ->assertHasErrors(['rolloverPercentage']);

        // Valid preview generation, step navigation and totals
        $comp = Livewire::actingAs($this->adminUser)
            ->test(TahunAnggaranIndex::class)
            ->set('rolloverSourceId', $this->taAktif->id)
            ->set('rolloverTargetId', $draftYear->id)
            ->set('rolloverScheme', 'percentage')
            ->set('rolloverPercentage', '20')
            ->call('generateRolloverPreview')
            ->assertHasNoErrors()
            ->assertSet('rolloverStep', 2);

        $this->assertGreaterThan(0, $comp->get('totalRolloverSourcePlafon'));
        $this->assertGreaterThan(0, $comp->get('totalRolloverTargetPlafon'));

        // Back to step 1
        $comp->call('previousRolloverStep')
            ->assertSet('rolloverStep', 1);

        // Close modal resets step & preview
        $comp->call('closeRolloverModal')
            ->assertSet('showRolloverModal', false)
            ->assertSet('rolloverStep', 1)
            ->assertSet('rolloverPreview', []);
    }

    public function test_rollover_handles_formatted_nominal_string_with_dots(): void
    {
        $this->assertSame(100000, TahunAnggaranIndex::parseRupiah('100.000'));
        $this->assertSame(1000, TahunAnggaranIndex::parseRupiah('1.000'));
        $this->assertSame(1000000, TahunAnggaranIndex::parseRupiah('1.000.000'));
        $this->assertSame(2500000, TahunAnggaranIndex::parseRupiah('Rp 2.500.000'));
        $this->assertSame(50000, TahunAnggaranIndex::parseRupiah(50000));
        $this->assertSame(0, TahunAnggaranIndex::parseRupiah(''));
        $this->assertSame(0, TahunAnggaranIndex::parseRupiah(null));

        $targetYear = TahunAnggaran::create(['tahun' => 2038, 'status' => 'draft']);

        $node = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->taAktif->id,
            'kode' => '9.40.1',
            'nama' => 'Pos Nominal Format',
            'depth' => 2,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        AlokasiAnggaran::create([
            'kode_anggaran_id' => $node->id,
            'tahun_anggaran_id' => $this->taAktif->id,
            'plafon_awal' => 50000000,
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(TahunAnggaranIndex::class)
            ->set('rolloverSourceId', $this->taAktif->id)
            ->set('rolloverTargetId', $targetYear->id)
            ->set('rolloverScheme', 'full')
            ->call('generateRolloverPreview');

        $preview = $component->get('rolloverPreview');
        $idx = collect($preview)->search(fn ($p) => $p['node_id'] === $node->id);
        $this->assertNotFalse($idx);

        // User enters formatted nominal string with thousand dots
        $preview[$idx]['target_plafon'] = '100.000';

        $component->set('rolloverPreview', $preview)
            ->call('executeRollover')
            ->assertHasNoErrors();

        $targetNode = KodeAnggaran::where('tahun_anggaran_id', $targetYear->id)->where('kode', '9.40.1')->firstOrFail();
        $alokasi = AlokasiAnggaran::where('kode_anggaran_id', $targetNode->id)->where('tahun_anggaran_id', $targetYear->id)->firstOrFail();

        // Must be saved as 100000, NOT truncated to 100
        $this->assertSame(100000, $alokasi->plafon_awal);
    }

    public function test_rollover_preview_search_and_filter_and_quick_adjustments(): void
    {
        $targetYear = TahunAnggaran::create(['tahun' => 2039, 'status' => 'draft']);

        $nodeGaji = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->taAktif->id,
            'kode' => '9.50.1',
            'nama' => 'Biaya Gaji Amil',
            'depth' => 2,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);
        $nodeListrik = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->taAktif->id,
            'kode' => '9.50.2',
            'nama' => 'Biaya Listrik Kantor',
            'depth' => 2,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        AlokasiAnggaran::create([
            'kode_anggaran_id' => $nodeGaji->id,
            'tahun_anggaran_id' => $this->taAktif->id,
            'plafon_awal' => 50000000,
        ]);
        AlokasiAnggaran::create([
            'kode_anggaran_id' => $nodeListrik->id,
            'tahun_anggaran_id' => $this->taAktif->id,
            'plafon_awal' => 10000000,
        ]);

        $comp = Livewire::actingAs($this->adminUser)
            ->test(TahunAnggaranIndex::class)
            ->set('rolloverSourceId', $this->taAktif->id)
            ->set('rolloverTargetId', $targetYear->id)
            ->set('rolloverScheme', 'full')
            ->call('generateRolloverPreview');

        $preview = $comp->get('rolloverPreview');
        $idxGaji = collect($preview)->search(fn ($p) => $p['node_id'] === $nodeGaji->id);
        $idxListrik = collect($preview)->search(fn ($p) => $p['node_id'] === $nodeListrik->id);

        // Search test: filter by name "Listrik"
        $comp->set('previewSearch', 'Listrik');
        $filtered = $comp->get('filteredRolloverPreview');
        $this->assertArrayHasKey($idxListrik, $filtered);
        $this->assertArrayNotHasKey($idxGaji, $filtered);

        // Quick adjust +100.000 and +1.000.000
        $comp->call('adjustItemPlafon', $idxListrik, 100000);
        $previewAfter100k = $comp->get('rolloverPreview');
        $this->assertSame(10100000, $previewAfter100k[$idxListrik]['target_plafon']);

        $comp->call('adjustItemPlafon', $idxListrik, 1000000);
        $previewAfter1m = $comp->get('rolloverPreview');
        $this->assertSame(11100000, $previewAfter1m[$idxListrik]['target_plafon']);

        // Status filter: changed
        $comp->set('previewSearch', '')
            ->set('previewFilter', 'changed');
        $changedFiltered = $comp->get('filteredRolloverPreview');
        $this->assertArrayHasKey($idxListrik, $changedFiltered);

        // Reset item to source
        $comp->call('resetItemToSource', $idxListrik);
        $previewReset = $comp->get('rolloverPreview');
        $this->assertSame(10000000, $previewReset[$idxListrik]['target_plafon']);

        // Set item to zero
        $comp->call('setItemToZero', $idxListrik);
        $previewZero = $comp->get('rolloverPreview');
        $this->assertSame(0, $previewZero[$idxListrik]['target_plafon']);

        // Bulk reset all to source
        $comp->call('resetAllPreviewToSource');
        $previewAllSource = $comp->get('rolloverPreview');
        $this->assertSame(10000000, $previewAllSource[$idxListrik]['target_plafon']);
        $this->assertSame(50000000, $previewAllSource[$idxGaji]['target_plafon']);

        // Bulk percentage +10%
        $comp->set('previewFilter', 'all');
        $comp->call('applyBulkPercentage', 10);
        $previewPct = $comp->get('rolloverPreview');
        $this->assertSame(11000000, $previewPct[$idxListrik]['target_plafon']);
        $this->assertSame(55000000, $previewPct[$idxGaji]['target_plafon']);

        // Bulk set all to zero
        $comp->call('setAllPreviewToZero');
        $previewAllZero = $comp->get('rolloverPreview');
        $this->assertSame(0, $previewAllZero[$idxListrik]['target_plafon']);
        $this->assertSame(0, $previewAllZero[$idxGaji]['target_plafon']);
    }

    public function test_audit_log_recorded_for_batch_and_import(): void
    {
        $draftYear = TahunAnggaran::create(['tahun' => 2032, 'status' => 'draft']);

        $node = KodeAnggaran::create([
            'tahun_anggaran_id' => $draftYear->id,
            'kode' => '9.10',
            'nama' => 'Audit Pos',
            'depth' => 1,
            'budget_status' => 'draft',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(KodeAnggaranIndex::class)
            ->set('tahun_anggaran_id', $draftYear->id)
            ->set('batchEditMode', true)
            ->set('batchEdits', [$node->id => 25000000])
            ->call('saveBatchEdits')
            ->assertHasNoErrors();

        $this->assertTrue(
            AuditLog::where('action', 'alokasi.batch_updated')
                ->where('entity_id', function ($q) use ($node, $draftYear) {
                    $q->select('id')->from('alokasi_anggaran')
                        ->where('kode_anggaran_id', $node->id)
                        ->where('tahun_anggaran_id', $draftYear->id);
                })
                ->exists()
        );
    }

    public function test_download_template_export(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(KodeAnggaranIndex::class)
            ->set('tahun_anggaran_id', $this->taAktif->id)
            ->call('downloadTemplate')
            ->assertFileDownloaded("Template_Alokasi_Anggaran_{$this->taAktif->tahun}.xlsx");
    }

    public function test_apply_import_updates_alokasi_anggaran(): void
    {
        $node = KodeAnggaran::create([
            'tahun_anggaran_id' => $this->taAktif->id,
            'kode' => '9.11',
            'nama' => 'Import Pos',
            'depth' => 1,
            'budget_status' => 'disahkan',
            'is_active' => true,
        ]);

        AlokasiAnggaran::create([
            'kode_anggaran_id' => $node->id,
            'tahun_anggaran_id' => $this->taAktif->id,
            'plafon_awal' => 30000000,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(KodeAnggaranIndex::class)
            ->set('tahun_anggaran_id', $this->taAktif->id)
            ->set('importDiff', [
                [
                    'node_id' => $node->id,
                    'kode' => '9.11',
                    'nama' => 'Import Pos',
                    'old_plafon' => 30000000,
                    'new_plafon' => 45000000,
                    'change_pct' => 50.0,
                    'direction' => 'up',
                ],
            ])
            ->set('importRevisionNote', 'Import penyesuaian RAB tahunan')
            ->call('applyImport')
            ->assertHasNoErrors();

        $alokasi = AlokasiAnggaran::where('kode_anggaran_id', $node->id)->where('tahun_anggaran_id', $this->taAktif->id)->first();
        $this->assertNotNull($alokasi);
        $this->assertSame(45000000, $alokasi->plafon_revisi);
        $this->assertSame('Import penyesuaian RAB tahunan', $alokasi->keterangan_revisi);

        $this->assertTrue(
            AuditLog::where('action', 'alokasi.imported')
                ->where('entity_id', $alokasi->id)
                ->exists()
        );
    }

    public function test_year_switcher_dispatches_event_and_updates_session(): void
    {
        $newYear = TahunAnggaran::create(['tahun' => 2033, 'status' => 'draft']);

        Livewire::test(YearSwitcher::class)
            ->call('switchYear', $newYear->id)
            ->assertDispatched('tahun-anggaran-changed', tahunAnggaranId: $newYear->id);

        $this->assertSame($newYear->id, session('tahun_anggaran_id'));
    }
}
