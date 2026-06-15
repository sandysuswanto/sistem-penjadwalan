<?php

namespace App\Console\Commands;

use App\Services\AntColonyScheduler;
use App\Models\Jadwal;
use Illuminate\Console\Command;

class GenerateScheduleCommand extends Command
{
    protected $signature = 'schedule:generate {--semester=ganjil} {--force}';
    protected $description = 'Generate course schedule using Ant Colony Optimization';

    public function handle()
    {
        $semester = $this->option('semester');
        $this->info("Starting ACO scheduling for semester: {$semester}");

        $scheduler = new AntColonyScheduler([
            'antCount' => 10,      // dari 30 turun ke 10
            'iterations' => 20     // dari 100 turun ke 20
        ]);

        $bestSchedule = $scheduler->run($semester);

        if (!$bestSchedule) {
            $this->error("Failed to generate any schedule.");
            return 1;
        }

        // Hapus jadwal lama jika force
        if ($this->option('force')) {
            Jadwal::whereIn('mata_kuliah_id', function ($q) use ($semester) {
                $q->select('id')->from('mata_kuliahs')->where('semester', $semester);
            })->delete();
        }

        // Simpan ke database
        foreach ($bestSchedule as $item) {
            Jadwal::updateOrCreate(
                [
                    'mata_kuliah_id' => $item->mata_kuliah_id,
                    'kelas_id' => $item->kelas_id,
                ],
                (array)$item
            );
        }

        $this->info("Schedule saved successfully. Fitness: " . $scheduler->evaluate($bestSchedule));
        return 0;
    }
}
