<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InputVoucher;
use App\Models\InputVoucherItem;
use App\Models\Item;
use App\Models\ItemCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class InputVoucherExcelSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = public_path('inputVoucher.xlsx');
        $logPath = storage_path('logs/input_voucher_import.log');

        if (!file_exists($filePath)) {
            echo "❌ File not found: $filePath\n";
            return;
        }

        // حذف البيانات القديمة
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        InputVoucherItem::truncate();
        InputVoucher::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // تعطيل تحقق المفاتيح الخارجية مؤقتًا
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Item::truncate();
        ItemCategory::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');


        Log::build(['driver' => 'single', 'path' => $logPath])
            ->info("🗑️ All previous input vouchers and items deleted.");
        Log::build(['driver' => 'single', 'path' => $logPath])
            ->info("==== Start import from inputVoucher.xlsx at " . now() . " ====");

        $spreadsheet = IOFactory::load($filePath);
        $allData = collect();

        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            $rows = $sheet->toArray(null, true, true, true);

            if (empty($rows)) {
                Log::build(['driver' => 'single', 'path' => $logPath])
                    ->warning("Sheet '$sheetName' is empty. Skipping...");
                continue;
            }

            $firstRow = reset($rows);
            $header = array_map('strtolower', array_map('trim', $firstRow));
            unset($rows[array_key_first($rows)]);

            $data = collect($rows)->map(function ($row) use ($header) {
                return array_combine($header, array_values($row));
            });

            $allData = $allData->merge($data);
        }

        $grouped = $allData->groupBy('number');

        $success = 0;
        $failed = 0;

        foreach ($grouped as $number => $entries) {
            $firstRow = $entries->first();

            $voucher = InputVoucher::create([
                'number' => $number,
                'date' => $this->formatDate($firstRow['date'] ?? now()),
                'notes' => $firstRow['notes'] ?? null,
                'stock_id' => $firstRow['stock_id'] ?? 1,
                'input_voucher_state_id' => ($firstRow['date_gov'] ?? '') === 'مستلم' ? 3 : 1,
                'date_receive' => $this->formatDate($firstRow['date'] ?? now()),
                'date_bill' => $this->formatDate($firstRow['date'] ?? now()),
                'user_create_id' => 1,
                'user_update_id' => 1,
            ]);

            foreach ($entries as $i => $entry) {
                $itemName = trim($entry['name'] ?? '');
                $categoryName = trim($entry['categroy'] ?? 'غير مصنف');

                $item = Item::where('name', $itemName)->first();

                if (!$item) {
                    $category = ItemCategory::firstOrCreate(
                        ['name' => $categoryName],
                        ['user_create_id' => 1, 'user_update_id' => 1]
                    );

                    $item = Item::create([
                        'name' => mb_substr($itemName, 0, 255),
                        'code' => null,
                        'description' => null,
                        'item_category_id' => $category->id,
                        'measuring_unit' => null,
                        'user_create_id' => 1,
                        'user_update_id' => 1,
                    ]);

                    Log::build(['driver' => 'single', 'path' => $logPath])
                        ->info("🆕 Created item '$itemName' under category '$categoryName'");
                }

                InputVoucherItem::create([
                    'input_voucher_id' => $voucher->id,
                    'item_id' => $item->id,
                    'count' => $entry['count'] ?? 0,
                    'price' => $entry['price'] * 10 ?? 0,
                    'value' => $entry['total'] * 10  ?? 0,
                    'notes' => $entry['notes'] ?? null,
                ]);

                $success++;
            }
        }

        Log::build(['driver' => 'single', 'path' => $logPath])
            ->info("✅ Finished: $success items imported, $failed failed.");
        echo "✅ Done. $success items imported, $failed failed.\n";
        echo "📄 Log file: $logPath\n";
    }

    private function formatDate($value)
    {
        if (!$value) return now();

        try {
            if ($value instanceof \DateTime) {
                return Carbon::instance($value)->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::error("❌ Invalid date format: " . json_encode($value));
            return now(); // fallback
        }
    }
}
