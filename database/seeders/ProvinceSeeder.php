<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Seeder;

class ProvinceSeeder extends Seeder
{
    /**
     * ข้อมูล 77 จังหวัด พร้อมจำนวนเขตเลือกตั้ง 400 เขต
     * อ้างอิงจาก กกต. (สำนักงานคณะกรรมการการเลือกตั้ง) 2566
     * https://www.ect.go.th/
     */
    public function run(): void
    {
        $provinces = [
            // ภาคเหนือ (9 จังหวัด) - 36 เขต
            ['code' => '50', 'name_th' => 'เชียงใหม่', 'name_en' => 'Chiang Mai', 'region' => 'north', 'total_constituencies' => 10, 'population' => 1779954],
            ['code' => '51', 'name_th' => 'ลำพูน', 'name_en' => 'Lamphun', 'region' => 'north', 'total_constituencies' => 2, 'population' => 404560],
            ['code' => '52', 'name_th' => 'ลำปาง', 'name_en' => 'Lampang', 'region' => 'north', 'total_constituencies' => 4, 'population' => 729658],
            ['code' => '53', 'name_th' => 'อุตรดิตถ์', 'name_en' => 'Uttaradit', 'region' => 'north', 'total_constituencies' => 3, 'population' => 454260],
            ['code' => '54', 'name_th' => 'แพร่', 'name_en' => 'Phrae', 'region' => 'north', 'total_constituencies' => 3, 'population' => 436103],
            ['code' => '55', 'name_th' => 'น่าน', 'name_en' => 'Nan', 'region' => 'north', 'total_constituencies' => 3, 'population' => 479431],
            ['code' => '56', 'name_th' => 'พะเยา', 'name_en' => 'Phayao', 'region' => 'north', 'total_constituencies' => 3, 'population' => 471081],
            ['code' => '57', 'name_th' => 'เชียงราย', 'name_en' => 'Chiang Rai', 'region' => 'north', 'total_constituencies' => 7, 'population' => 1285817],
            ['code' => '58', 'name_th' => 'แม่ฮ่องสอน', 'name_en' => 'Mae Hong Son', 'region' => 'north', 'total_constituencies' => 1, 'population' => 285586],

            // ภาคตะวันออกเฉียงเหนือ (20 จังหวัด) - 133 เขต
            ['code' => '30', 'name_th' => 'นครราชสีมา', 'name_en' => 'Nakhon Ratchasima', 'region' => 'northeast', 'total_constituencies' => 16, 'population' => 2634154],
            ['code' => '31', 'name_th' => 'บุรีรัมย์', 'name_en' => 'Buri Ram', 'region' => 'northeast', 'total_constituencies' => 10, 'population' => 1595065],
            ['code' => '32', 'name_th' => 'สุรินทร์', 'name_en' => 'Surin', 'region' => 'northeast', 'total_constituencies' => 8, 'population' => 1388359],
            ['code' => '33', 'name_th' => 'ศรีสะเกษ', 'name_en' => 'Si Sa Ket', 'region' => 'northeast', 'total_constituencies' => 9, 'population' => 1463615],
            ['code' => '34', 'name_th' => 'อุบลราชธานี', 'name_en' => 'Ubon Ratchathani', 'region' => 'northeast', 'total_constituencies' => 11, 'population' => 1878146],
            ['code' => '35', 'name_th' => 'ยโสธร', 'name_en' => 'Yasothon', 'region' => 'northeast', 'total_constituencies' => 3, 'population' => 536018],
            ['code' => '36', 'name_th' => 'ชัยภูมิ', 'name_en' => 'Chaiyaphum', 'region' => 'northeast', 'total_constituencies' => 7, 'population' => 1127423],
            ['code' => '37', 'name_th' => 'อำนาจเจริญ', 'name_en' => 'Amnat Charoen', 'region' => 'northeast', 'total_constituencies' => 2, 'population' => 375689],
            ['code' => '38', 'name_th' => 'บึงกาฬ', 'name_en' => 'Bueng Kan', 'region' => 'northeast', 'total_constituencies' => 2, 'population' => 422091],
            ['code' => '39', 'name_th' => 'หนองบัวลำภู', 'name_en' => 'Nong Bua Lam Phu', 'region' => 'northeast', 'total_constituencies' => 3, 'population' => 509834],
            ['code' => '40', 'name_th' => 'ขอนแก่น', 'name_en' => 'Khon Kaen', 'region' => 'northeast', 'total_constituencies' => 11, 'population' => 1792905],
            ['code' => '41', 'name_th' => 'อุดรธานี', 'name_en' => 'Udon Thani', 'region' => 'northeast', 'total_constituencies' => 10, 'population' => 1570500],
            ['code' => '42', 'name_th' => 'เลย', 'name_en' => 'Loei', 'region' => 'northeast', 'total_constituencies' => 4, 'population' => 637624],
            ['code' => '43', 'name_th' => 'หนองคาย', 'name_en' => 'Nong Khai', 'region' => 'northeast', 'total_constituencies' => 3, 'population' => 517260],
            ['code' => '44', 'name_th' => 'มหาสารคาม', 'name_en' => 'Maha Sarakham', 'region' => 'northeast', 'total_constituencies' => 6, 'population' => 960359],
            ['code' => '45', 'name_th' => 'ร้อยเอ็ด', 'name_en' => 'Roi Et', 'region' => 'northeast', 'total_constituencies' => 8, 'population' => 1306104],
            ['code' => '46', 'name_th' => 'กาฬสินธุ์', 'name_en' => 'Kalasin', 'region' => 'northeast', 'total_constituencies' => 6, 'population' => 983720],
            ['code' => '47', 'name_th' => 'สกลนคร', 'name_en' => 'Sakon Nakhon', 'region' => 'northeast', 'total_constituencies' => 7, 'population' => 1148052],
            ['code' => '48', 'name_th' => 'นครพนม', 'name_en' => 'Nakhon Phanom', 'region' => 'northeast', 'total_constituencies' => 4, 'population' => 714740],
            ['code' => '49', 'name_th' => 'มุกดาหาร', 'name_en' => 'Mukdahan', 'region' => 'northeast', 'total_constituencies' => 2, 'population' => 352837],

            // ภาคกลาง (22 จังหวัด รวม กทม.) - 122 เขต
            ['code' => '10', 'name_th' => 'กรุงเทพมหานคร', 'name_en' => 'Bangkok', 'region' => 'central', 'total_constituencies' => 33, 'population' => 5494910],
            ['code' => '11', 'name_th' => 'สมุทรปราการ', 'name_en' => 'Samut Prakan', 'region' => 'central', 'total_constituencies' => 8, 'population' => 1349263],
            ['code' => '12', 'name_th' => 'นนทบุรี', 'name_en' => 'Nonthaburi', 'region' => 'central', 'total_constituencies' => 8, 'population' => 1278540],
            ['code' => '13', 'name_th' => 'ปทุมธานี', 'name_en' => 'Pathum Thani', 'region' => 'central', 'total_constituencies' => 7, 'population' => 1176412],
            ['code' => '14', 'name_th' => 'พระนครศรีอยุธยา', 'name_en' => 'Phra Nakhon Si Ayutthaya', 'region' => 'central', 'total_constituencies' => 5, 'population' => 815384],
            ['code' => '15', 'name_th' => 'อ่างทอง', 'name_en' => 'Ang Thong', 'region' => 'central', 'total_constituencies' => 2, 'population' => 280115],
            ['code' => '16', 'name_th' => 'ลพบุรี', 'name_en' => 'Lop Buri', 'region' => 'central', 'total_constituencies' => 4, 'population' => 753828],
            ['code' => '17', 'name_th' => 'สิงห์บุรี', 'name_en' => 'Sing Buri', 'region' => 'central', 'total_constituencies' => 1, 'population' => 206804],
            ['code' => '18', 'name_th' => 'ชัยนาท', 'name_en' => 'Chai Nat', 'region' => 'central', 'total_constituencies' => 2, 'population' => 326949],
            ['code' => '19', 'name_th' => 'สระบุรี', 'name_en' => 'Saraburi', 'region' => 'central', 'total_constituencies' => 4, 'population' => 645641],
            ['code' => '60', 'name_th' => 'นครสวรรค์', 'name_en' => 'Nakhon Sawan', 'region' => 'central', 'total_constituencies' => 6, 'population' => 1059887],
            ['code' => '61', 'name_th' => 'อุทัยธานี', 'name_en' => 'Uthai Thani', 'region' => 'central', 'total_constituencies' => 2, 'population' => 328292],
            ['code' => '62', 'name_th' => 'กำแพงเพชร', 'name_en' => 'Kamphaeng Phet', 'region' => 'central', 'total_constituencies' => 4, 'population' => 725853],
            ['code' => '63', 'name_th' => 'ตาก', 'name_en' => 'Tak', 'region' => 'central', 'total_constituencies' => 3, 'population' => 664450],
            ['code' => '64', 'name_th' => 'สุโขทัย', 'name_en' => 'Sukhothai', 'region' => 'central', 'total_constituencies' => 4, 'population' => 597207],
            ['code' => '65', 'name_th' => 'พิษณุโลก', 'name_en' => 'Phitsanulok', 'region' => 'central', 'total_constituencies' => 5, 'population' => 865368],
            ['code' => '66', 'name_th' => 'พิจิตร', 'name_en' => 'Phichit', 'region' => 'central', 'total_constituencies' => 3, 'population' => 539529],
            ['code' => '67', 'name_th' => 'เพชรบูรณ์', 'name_en' => 'Phetchabun', 'region' => 'central', 'total_constituencies' => 6, 'population' => 993693],
            ['code' => '72', 'name_th' => 'สุพรรณบุรี', 'name_en' => 'Suphan Buri', 'region' => 'central', 'total_constituencies' => 5, 'population' => 842123],
            ['code' => '73', 'name_th' => 'นครปฐม', 'name_en' => 'Nakhon Pathom', 'region' => 'central', 'total_constituencies' => 6, 'population' => 926732],
            ['code' => '74', 'name_th' => 'สมุทรสาคร', 'name_en' => 'Samut Sakhon', 'region' => 'central', 'total_constituencies' => 3, 'population' => 579113],
            ['code' => '75', 'name_th' => 'สมุทรสงคราม', 'name_en' => 'Samut Songkhram', 'region' => 'central', 'total_constituencies' => 1, 'population' => 193729],

            // ภาคตะวันออก (8 จังหวัด) - 29 เขต
            ['code' => '20', 'name_th' => 'ชลบุรี', 'name_en' => 'Chon Buri', 'region' => 'east', 'total_constituencies' => 9, 'population' => 1558301],
            ['code' => '21', 'name_th' => 'ระยอง', 'name_en' => 'Rayong', 'region' => 'east', 'total_constituencies' => 4, 'population' => 742323],
            ['code' => '22', 'name_th' => 'จันทบุรี', 'name_en' => 'Chanthaburi', 'region' => 'east', 'total_constituencies' => 3, 'population' => 540221],
            ['code' => '23', 'name_th' => 'ตราด', 'name_en' => 'Trat', 'region' => 'east', 'total_constituencies' => 1, 'population' => 229235],
            ['code' => '24', 'name_th' => 'ฉะเชิงเทรา', 'name_en' => 'Chachoengsao', 'region' => 'east', 'total_constituencies' => 4, 'population' => 716689],
            ['code' => '25', 'name_th' => 'ปราจีนบุรี', 'name_en' => 'Prachin Buri', 'region' => 'east', 'total_constituencies' => 3, 'population' => 487026],
            ['code' => '26', 'name_th' => 'นครนายก', 'name_en' => 'Nakhon Nayok', 'region' => 'east', 'total_constituencies' => 2, 'population' => 260654],
            ['code' => '27', 'name_th' => 'สระแก้ว', 'name_en' => 'Sa Kaeo', 'region' => 'east', 'total_constituencies' => 3, 'population' => 564615],

            // ภาคตะวันตก (5 จังหวัด) - 16 เขต
            ['code' => '70', 'name_th' => 'ราชบุรี', 'name_en' => 'Ratchaburi', 'region' => 'west', 'total_constituencies' => 5, 'population' => 870913],
            ['code' => '71', 'name_th' => 'กาญจนบุรี', 'name_en' => 'Kanchanaburi', 'region' => 'west', 'total_constituencies' => 5, 'population' => 898158],
            ['code' => '76', 'name_th' => 'เพชรบุรี', 'name_en' => 'Phetchaburi', 'region' => 'west', 'total_constituencies' => 3, 'population' => 483448],
            ['code' => '77', 'name_th' => 'ประจวบคีรีขันธ์', 'name_en' => 'Prachuap Khiri Khan', 'region' => 'west', 'total_constituencies' => 3, 'population' => 547338],

            // ภาคใต้ (14 จังหวัด) - 60 เขต
            ['code' => '80', 'name_th' => 'นครศรีธรรมราช', 'name_en' => 'Nakhon Si Thammarat', 'region' => 'south', 'total_constituencies' => 10, 'population' => 1563637],
            ['code' => '81', 'name_th' => 'กระบี่', 'name_en' => 'Krabi', 'region' => 'south', 'total_constituencies' => 3, 'population' => 479541],
            ['code' => '82', 'name_th' => 'พังงา', 'name_en' => 'Phang Nga', 'region' => 'south', 'total_constituencies' => 2, 'population' => 270320],
            ['code' => '83', 'name_th' => 'ภูเก็ต', 'name_en' => 'Phuket', 'region' => 'south', 'total_constituencies' => 3, 'population' => 418864],
            ['code' => '84', 'name_th' => 'สุราษฎร์ธานี', 'name_en' => 'Surat Thani', 'region' => 'south', 'total_constituencies' => 6, 'population' => 1067846],
            ['code' => '85', 'name_th' => 'ระนอง', 'name_en' => 'Ranong', 'region' => 'south', 'total_constituencies' => 1, 'population' => 192887],
            ['code' => '86', 'name_th' => 'ชุมพร', 'name_en' => 'Chumphon', 'region' => 'south', 'total_constituencies' => 3, 'population' => 509650],
            ['code' => '90', 'name_th' => 'สงขลา', 'name_en' => 'Songkhla', 'region' => 'south', 'total_constituencies' => 9, 'population' => 1439713],
            ['code' => '91', 'name_th' => 'สตูล', 'name_en' => 'Satun', 'region' => 'south', 'total_constituencies' => 2, 'population' => 320294],
            ['code' => '92', 'name_th' => 'ตรัง', 'name_en' => 'Trang', 'region' => 'south', 'total_constituencies' => 4, 'population' => 641510],
            ['code' => '93', 'name_th' => 'พัทลุง', 'name_en' => 'Phatthalung', 'region' => 'south', 'total_constituencies' => 3, 'population' => 525295],
            ['code' => '94', 'name_th' => 'ปัตตานี', 'name_en' => 'Pattani', 'region' => 'south', 'total_constituencies' => 4, 'population' => 727541],
            ['code' => '95', 'name_th' => 'ยะลา', 'name_en' => 'Yala', 'region' => 'south', 'total_constituencies' => 3, 'population' => 533027],
            ['code' => '96', 'name_th' => 'นราธิวาส', 'name_en' => 'Narathiwat', 'region' => 'south', 'total_constituencies' => 4, 'population' => 808029],
        ];

        $created = 0;
        $updated = 0;

        foreach ($provinces as $provinceData) {
            $province = Province::where('code', $provinceData['code'])->first();

            if ($province) {
                $province->update($provinceData);
                $updated++;
            } else {
                Province::create($provinceData);
                $created++;
            }
        }

        $this->command->info("Provinces: {$created} created, {$updated} updated.");
    }
}
