<?php

namespace Database\Seeders;

use App\Models\Constituency;
use App\Models\Election;
use App\Models\Party;
use App\Models\Province;
use Illuminate\Database\Seeder;

/**
 * Seeder สำหรับข้อมูลการเลือกตั้ง ส.ส. 2569
 * อ้างอิง: กกต. (https://www.ect.go.th/)
 * วันเลือกตั้ง: 8 กุมภาพันธ์ 2569
 */
class Election2569Seeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Election 2569 data...');

        // 1. สร้างข้อมูลการเลือกตั้ง
        $election = $this->createElection();

        // 2. สร้างข้อมูลพรรคการเมือง
        $this->seedParties();

        // 3. อัปเดตจำนวนเขตเลือกตั้งตามจังหวัด
        $this->updateConstituencyAllocation();

        $this->command->info('Election 2569 seeding completed!');
    }

    protected function createElection(): Election
    {
        $this->command->info('Creating election...');

        return Election::updateOrCreate(
            ['name' => 'การเลือกตั้ง ส.ส. 2569'],
            [
                'name_en' => 'General Election 2026',
                'type' => 'general',
                'description' => 'การเลือกตั้งสมาชิกสภาผู้แทนราษฎรเป็นการทั่วไป พ.ศ. 2569 หลังจากพระราชกฤษฎีกายุบสภาผู้แทนราษฎร พ.ศ. 2568',
                'election_date' => '2026-02-08',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'status' => 'upcoming',
                'total_eligible_voters' => 52000000,
                'is_active' => true,
                'settings' => [
                    'constituency_seats' => 400,
                    'party_list_seats' => 100,
                    'total_seats' => 500,
                    'total_parties' => 57,
                    'total_candidates_constituency' => 3526,
                    'total_candidates_party_list' => 1570,
                    'pm_candidates' => 94,
                    'registration_period' => [
                        'constituency' => ['start' => '2568-12-27', 'end' => '2568-12-31'],
                        'party_list' => ['start' => '2568-12-27', 'end' => '2568-12-31'],
                    ],
                    'advance_voting_date' => '2569-02-01',
                    'announcement_date' => '2569-01-07',
                ],
            ],
        );
    }

    protected function seedParties(): void
    {
        $this->command->info('Seeding political parties (57 parties)...');

        $parties = $this->getParties2569();

        $created = 0;
        $updated = 0;

        foreach ($parties as $partyData) {
            $party = Party::where('name_th', $partyData['name_th'])->first();

            if ($party) {
                $party->update($partyData);
                $updated++;
            } else {
                Party::create($partyData);
                $created++;
            }
        }

        $this->command->info("Parties: {$created} created, {$updated} updated");
    }

    protected function updateConstituencyAllocation(): void
    {
        $this->command->info('Updating constituency allocation...');

        $allocation = $this->getConstituencyAllocation();
        $provinces = Province::all()->keyBy('name_th');

        $updated = 0;

        foreach ($allocation as $provinceName => $seatCount) {
            $province = $provinces->get($provinceName);

            if ($province) {
                $province->update(['total_constituencies' => $seatCount]);

                for ($i = 1; $i <= $seatCount; $i++) {
                    Constituency::updateOrCreate(
                        [
                            'province_id' => $province->id,
                            'number' => $i,
                        ],
                        [
                            'name' => "เขตเลือกตั้งที่ {$i}",
                        ],
                    );
                }
                $updated++;
            }
        }

        $this->command->info("Updated {$updated} provinces");
    }

    protected function getParties2569(): array
    {
        return [
            ['party_number' => 1, 'name_th' => 'พรรคครูไทยเพื่อประชาชน', 'name_en' => 'Thai Teacher For People Party', 'abbreviation' => 'ครูไทย', 'color' => '#4A90A4', 'is_active' => true],
            ['party_number' => 2, 'name_th' => 'พรรคพลังสังคมใหม่', 'name_en' => 'New Social Power Party', 'abbreviation' => 'พลังสังคมใหม่', 'color' => '#FF6B35', 'is_active' => true],
            ['party_number' => 3, 'name_th' => 'พรรคไทยก้าวหน้า', 'name_en' => 'Thai Progressive Party', 'abbreviation' => 'ไทยก้าวหน้า', 'color' => '#2ECC71', 'is_active' => true],
            ['party_number' => 4, 'name_th' => 'พรรคประชาธิปไตยใหม่', 'name_en' => 'New Democracy Party', 'abbreviation' => 'ประชาธิปไตยใหม่', 'color' => '#27AE60', 'is_active' => true],
            ['party_number' => 5, 'name_th' => 'พรรคประชาชาติ', 'name_en' => 'Prachachat Party', 'abbreviation' => 'ประชาชาติ', 'color' => '#00A651', 'is_active' => true],
            ['party_number' => 6, 'name_th' => 'พรรคแนวทางใหม่', 'name_en' => 'New Way Party', 'abbreviation' => 'แนวทางใหม่', 'color' => '#9B59B6', 'is_active' => true],
            ['party_number' => 7, 'name_th' => 'พรรคพลังธรรมใหม่', 'name_en' => 'New Moral Power Party', 'abbreviation' => 'พลังธรรมใหม่', 'color' => '#F39C12', 'is_active' => true],
            ['party_number' => 8, 'name_th' => 'พรรคประชาธิปัตย์', 'name_en' => 'Democrat Party', 'abbreviation' => 'ปชป.', 'color' => '#00AEEF', 'is_active' => true, 'founded_year' => 1946, 'leader_name' => 'จุรินทร์ ลักษณวิศิษฏ์', 'website' => 'https://www.democrat.or.th'],
            ['party_number' => 9, 'name_th' => 'พรรคเพื่อไทย', 'name_en' => 'Pheu Thai Party', 'abbreviation' => 'พท.', 'color' => '#E31E25', 'is_active' => true, 'founded_year' => 2008, 'leader_name' => 'แพทองธาร ชินวัตร', 'website' => 'https://ptp.or.th'],
            ['party_number' => 10, 'name_th' => 'พรรคชาติไทยพัฒนา', 'name_en' => 'Chart Thai Pattana Party', 'abbreviation' => 'ชทพ.', 'color' => '#8B4513', 'is_active' => true, 'founded_year' => 2008, 'leader_name' => 'วราวุธ ศิลปอาชา'],
            ['party_number' => 11, 'name_th' => 'พรรคไทยภักดี', 'name_en' => 'Thai Pakdee Party', 'abbreviation' => 'ไทยภักดี', 'color' => '#1E90FF', 'is_active' => true],
            ['party_number' => 12, 'name_th' => 'พรรคพลังปวงชนไทย', 'name_en' => 'Thai People Power Party', 'abbreviation' => 'พปชท.', 'color' => '#DC143C', 'is_active' => true],
            ['party_number' => 13, 'name_th' => 'พรรคท้องที่ไทย', 'name_en' => 'Thai Local Party', 'abbreviation' => 'ท้องที่ไทย', 'color' => '#228B22', 'is_active' => true],
            ['party_number' => 14, 'name_th' => 'พรรคทางเลือกใหม่', 'name_en' => 'New Alternative Party', 'abbreviation' => 'ทางเลือกใหม่', 'color' => '#FF4500', 'is_active' => true],
            ['party_number' => 15, 'name_th' => 'พรรคพลังสยาม', 'name_en' => 'Siam Power Party', 'abbreviation' => 'พลังสยาม', 'color' => '#4169E1', 'is_active' => true],
            ['party_number' => 16, 'name_th' => 'พรรคเพื่อชาติไทย', 'name_en' => 'For Thai Nation Party', 'abbreviation' => 'เพื่อชาติไทย', 'color' => '#CD5C5C', 'is_active' => true],
            ['party_number' => 17, 'name_th' => 'พรรคไทยสร้างไทย', 'name_en' => 'Thai Sang Thai Party', 'abbreviation' => 'ไทยสร้างไทย', 'color' => '#FFC107', 'is_active' => true, 'founded_year' => 2021, 'leader_name' => 'คุณหญิงสุดารัตน์ เกยุราพันธุ์'],
            ['party_number' => 18, 'name_th' => 'พรรคเสรีรวมไทย', 'name_en' => 'Thai Liberal Party', 'abbreviation' => 'สรท.', 'color' => '#FF69B4', 'is_active' => true, 'founded_year' => 2018, 'leader_name' => 'พลตำรวจเอก เสรีพิศุทธ์ เตมียเวส'],
            ['party_number' => 19, 'name_th' => 'พรรคภราดรภาพ', 'name_en' => 'Fraternity Party', 'abbreviation' => 'ภราดรภาพ', 'color' => '#20B2AA', 'is_active' => true],
            ['party_number' => 20, 'name_th' => 'พรรคแผ่นดินธรรม', 'name_en' => 'Phaendin Dharma Party', 'abbreviation' => 'แผ่นดินธรรม', 'color' => '#DAA520', 'is_active' => true],
            ['party_number' => 21, 'name_th' => 'พรรคมิติใหม่', 'name_en' => 'New Dimension Party', 'abbreviation' => 'มิติใหม่', 'color' => '#7B68EE', 'is_active' => true],
            ['party_number' => 22, 'name_th' => 'พรรคเพื่อไทยรวมพลัง', 'name_en' => 'Pheu Thai Ruam Palang Party', 'abbreviation' => 'พท.รวมพลัง', 'color' => '#B22222', 'is_active' => true],
            ['party_number' => 23, 'name_th' => 'พรรคชาติรุ่งเรือง', 'name_en' => 'Prosperous Nation Party', 'abbreviation' => 'ชาติรุ่งเรือง', 'color' => '#6B8E23', 'is_active' => true],
            ['party_number' => 24, 'name_th' => 'พรรครวมพลัง', 'name_en' => 'United Power Party', 'abbreviation' => 'รวมพลัง', 'color' => '#4682B4', 'is_active' => true],
            ['party_number' => 25, 'name_th' => 'พรรคเพื่อชาติ', 'name_en' => 'For Nation Party', 'abbreviation' => 'เพื่อชาติ', 'color' => '#8B0000', 'is_active' => true],
            ['party_number' => 26, 'name_th' => 'พรรคไทรักธรรม', 'name_en' => 'Thai Rak Tham Party', 'abbreviation' => 'ไทรักธรรม', 'color' => '#556B2F', 'is_active' => true],
            ['party_number' => 27, 'name_th' => 'พรรคราษฎร', 'name_en' => 'Ratsadon Party', 'abbreviation' => 'ราษฎร', 'color' => '#C71585', 'is_active' => true],
            ['party_number' => 28, 'name_th' => 'พรรคคลองไทย', 'name_en' => 'Klong Thai Party', 'abbreviation' => 'คลองไทย', 'color' => '#00CED1', 'is_active' => true],
            ['party_number' => 29, 'name_th' => 'พรรคพลังท้องถิ่นไท', 'name_en' => 'Thai Local Power Party', 'abbreviation' => 'พลังท้องถิ่นไท', 'color' => '#32CD32', 'is_active' => true],
            ['party_number' => 30, 'name_th' => 'พรรคใหม่', 'name_en' => 'Mai Party', 'abbreviation' => 'ใหม่', 'color' => '#FF8C00', 'is_active' => true],
            ['party_number' => 31, 'name_th' => 'พรรคพลเมืองไทย', 'name_en' => 'Thai Citizen Party', 'abbreviation' => 'พลเมืองไทย', 'color' => '#9932CC', 'is_active' => true],
            ['party_number' => 32, 'name_th' => 'พรรคประชากรไทย', 'name_en' => 'Thai Population Party', 'abbreviation' => 'ประชากรไทย', 'color' => '#3CB371', 'is_active' => true],
            ['party_number' => 33, 'name_th' => 'พรรครักษ์ผืนป่าประเทศไทย', 'name_en' => 'Protect Thai Forest Party', 'abbreviation' => 'รักษ์ผืนป่า', 'color' => '#006400', 'is_active' => true],
            ['party_number' => 34, 'name_th' => 'พรรคกล้าธรรม', 'name_en' => 'Kla Tham Party', 'abbreviation' => 'กล้าธรรม', 'color' => '#800080', 'is_active' => true],
            ['party_number' => 35, 'name_th' => 'พรรครวมไทยสร้างชาติ', 'name_en' => 'United Thai Nation Party', 'abbreviation' => 'รทสช.', 'color' => '#6B21A8', 'is_active' => true, 'founded_year' => 2021, 'leader_name' => 'พีระพันธุ์ สาลีรัฐวิภาค'],
            ['party_number' => 36, 'name_th' => 'พรรคชาติพัฒนากล้า', 'name_en' => 'Chart Pattana Kla Party', 'abbreviation' => 'ชพก.', 'color' => '#4B0082', 'is_active' => true, 'founded_year' => 2020, 'leader_name' => 'กรณ์ จาติกวณิช'],
            ['party_number' => 37, 'name_th' => 'พรรคภูมิใจไทย', 'name_en' => 'Bhumjaithai Party', 'abbreviation' => 'ภท.', 'color' => '#0066B3', 'is_active' => true, 'founded_year' => 2008, 'leader_name' => 'อนุทิน ชาญวีรกูล', 'website' => 'https://www.bhumjaithai.com'],
            ['party_number' => 38, 'name_th' => 'พรรคสังคมประชาธิปไตยไทย', 'name_en' => 'Thai Social Democratic Party', 'abbreviation' => 'สปท.', 'color' => '#E74C3C', 'is_active' => true],
            ['party_number' => 39, 'name_th' => 'พรรคประชาภิวัฒน์', 'name_en' => 'People Development Party', 'abbreviation' => 'ประชาภิวัฒน์', 'color' => '#1ABC9C', 'is_active' => true],
            ['party_number' => 40, 'name_th' => 'พรรคสร้างอนาคตไทย', 'name_en' => 'Build Thai Future Party', 'abbreviation' => 'สอท.', 'color' => '#3498DB', 'is_active' => true],
            ['party_number' => 41, 'name_th' => 'พรรคเปลี่ยน', 'name_en' => 'Change Party', 'abbreviation' => 'เปลี่ยน', 'color' => '#E67E22', 'is_active' => true],
            ['party_number' => 42, 'name_th' => 'พรรคก้าวใหม่', 'name_en' => 'New Step Party', 'abbreviation' => 'ก้าวใหม่', 'color' => '#16A085', 'is_active' => true],
            ['party_number' => 43, 'name_th' => 'พรรคพลังประชารัฐ', 'name_en' => 'Palang Pracharath Party', 'abbreviation' => 'พปชร.', 'color' => '#1E3A8A', 'is_active' => true, 'founded_year' => 2018, 'leader_name' => 'พลเอก ประวิตร วงษ์สุวรรณ', 'website' => 'https://www.pprp.or.th'],
            ['party_number' => 44, 'name_th' => 'พรรคแรงงานสร้างชาติ', 'name_en' => 'Labor Build Nation Party', 'abbreviation' => 'รสช.', 'color' => '#D35400', 'is_active' => true],
            ['party_number' => 45, 'name_th' => 'พรรคเพื่อแผ่นดิน', 'name_en' => 'For Motherland Party', 'abbreviation' => 'พผด.', 'color' => '#27AE60', 'is_active' => true],
            ['party_number' => 46, 'name_th' => 'พรรคประชาชน', 'name_en' => "People's Party", 'abbreviation' => 'ปชช.', 'color' => '#FF6B00', 'is_active' => true, 'founded_year' => 2024, 'leader_name' => 'ณัฐพงษ์ เรืองปัญญาวุฒิ', 'website' => 'https://peoplesparty.or.th', 'slogan' => 'คนเท่ากัน'],
            ['party_number' => 47, 'name_th' => 'พรรคถิ่นกาขาวชาววิไล', 'name_en' => 'White Crow Land Party', 'abbreviation' => 'ถิ่นกาขาว', 'color' => '#FFFFFF', 'secondary_color' => '#000000', 'is_active' => true],
            ['party_number' => 48, 'name_th' => 'พรรครักประเทศไทย', 'name_en' => 'Love Thailand Party', 'abbreviation' => 'รปท.', 'color' => '#C0392B', 'is_active' => true],
            ['party_number' => 49, 'name_th' => 'พรรคพลังเพื่อไทย', 'name_en' => 'Power For Thai Party', 'abbreviation' => 'พพท.', 'color' => '#922B21', 'is_active' => true],
            ['party_number' => 50, 'name_th' => 'พรรคไทยเป็นหนึ่ง', 'name_en' => 'Thai As One Party', 'abbreviation' => 'ทป.', 'color' => '#2980B9', 'is_active' => true],
            ['party_number' => 51, 'name_th' => 'พรรคไทยศรีวิไลย์', 'name_en' => 'Thai Civilized Party', 'abbreviation' => 'ไทยศรีวิไลย์', 'color' => '#8E44AD', 'is_active' => true],
            ['party_number' => 52, 'name_th' => 'พรรคประชาสามัคคี', 'name_en' => 'People Unity Party', 'abbreviation' => 'ประชาสามัคคี', 'color' => '#F1C40F', 'is_active' => true],
            ['party_number' => 53, 'name_th' => 'พรรคไทยธรรม', 'name_en' => 'Thai Tham Party', 'abbreviation' => 'ไทยธรรม', 'color' => '#D4AC0D', 'is_active' => true],
            ['party_number' => 54, 'name_th' => 'พรรคกสิกรไทย', 'name_en' => 'Thai Farmer Party', 'abbreviation' => 'กสิกรไทย', 'color' => '#229954', 'is_active' => true],
            ['party_number' => 55, 'name_th' => 'พรรคประชานิยม', 'name_en' => 'Populist Party', 'abbreviation' => 'ประชานิยม', 'color' => '#E91E63', 'is_active' => true],
            ['party_number' => 56, 'name_th' => 'พรรคประชาสันติ', 'name_en' => 'People Peace Party', 'abbreviation' => 'ประชาสันติ', 'color' => '#00BCD4', 'is_active' => true],
            ['party_number' => 57, 'name_th' => 'พรรคทวงคืนผืนป่าประเทศไทย', 'name_en' => 'Reclaim Forest Party', 'abbreviation' => 'ทวงคืนป่า', 'color' => '#4CAF50', 'is_active' => true],
        ];
    }

    protected function getConstituencyAllocation(): array
    {
        return [
            'กรุงเทพมหานคร' => 33,
            'นครราชสีมา' => 16,
            'ขอนแก่น' => 11,
            'อุบลราชธานี' => 11,
            'เชียงใหม่' => 10,
            'บุรีรัมย์' => 10,
            'อุดรธานี' => 10,
            'ชลบุรี' => 9,
            'นครศรีธรรมราช' => 9,
            'ศรีสะเกษ' => 9,
            'ร้อยเอ็ด' => 8,
            'สุรินทร์' => 8,
            'สงขลา' => 8,
            'เพชรบูรณ์' => 7,
            'สกลนคร' => 7,
            'มหาสารคาม' => 7,
            'สมุทรปราการ' => 7,
            'ชัยภูมิ' => 7,
            'นครปฐม' => 6,
            'เชียงราย' => 6,
            'ระยอง' => 5,
            'กาฬสินธุ์' => 5,
            'ปทุมธานี' => 5,
            'นนทบุรี' => 5,
            'นครพนม' => 5,
            'สุราษฎร์ธานี' => 5,
            'พิษณุโลก' => 5,
            'สระบุรี' => 5,
            'ลำปาง' => 5,
            'ยโสธร' => 4,
            'สุพรรณบุรี' => 4,
            'กาญจนบุรี' => 4,
            'พระนครศรีอยุธยา' => 4,
            'นครสวรรค์' => 4,
            'หนองบัวลำภู' => 4,
            'เลย' => 4,
            'ตรัง' => 4,
            'ฉะเชิงเทรา' => 4,
            'ลพบุรี' => 4,
            'ราชบุรี' => 4,
            'พัทลุง' => 4,
            'หนองคาย' => 4,
            'เพชรบุรี' => 3,
            'จันทบุรี' => 3,
            'ลำพูน' => 3,
            'สุโขทัย' => 3,
            'กำแพงเพชร' => 3,
            'อุตรดิตถ์' => 3,
            'พิจิตร' => 3,
            'น่าน' => 3,
            'แพร่' => 3,
            'ตาก' => 3,
            'ปราจีนบุรี' => 3,
            'สระแก้ว' => 3,
            'ปัตตานี' => 3,
            'ยะลา' => 3,
            'นราธิวาส' => 3,
            'ชุมพร' => 3,
            'กระบี่' => 3,
            'อำนาจเจริญ' => 2,
            'มุกดาหาร' => 2,
            'บึงกาฬ' => 2,
            'พะเยา' => 2,
            'อุทัยธานี' => 2,
            'ชัยนาท' => 2,
            'สิงห์บุรี' => 1,
            'อ่างทอง' => 2,
            'นครนายก' => 2,
            'ตราด' => 2,
            'สมุทรสงคราม' => 1,
            'สมุทรสาคร' => 3,
            'ประจวบคีรีขันธ์' => 3,
            'พังงา' => 2,
            'ภูเก็ต' => 2,
            'ระนอง' => 1,
            'สตูล' => 2,
            'แม่ฮ่องสอน' => 1,
        ];
    }
}
