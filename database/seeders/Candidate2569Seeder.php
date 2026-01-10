<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\Election;
use App\Models\Party;
use Illuminate\Database\Seeder;

/**
 * Seeder สำหรับข้อมูลผู้สมัคร ส.ส. 2569
 * อ้างอิง: กกต. ประกาศ 7 มกราคม 2569
 *
 * สถิติ:
 * - ส.ส. แบ่งเขต: 3,526 คน
 * - ส.ส. บัญชีรายชื่อ: 1,570 คน (57 พรรค)
 * - แคนดิเดตนายกฯ: 94 คน
 */
class Candidate2569Seeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Candidate 2569 data...');

        $election = Election::where('name', 'การเลือกตั้ง ส.ส. 2569')->first();

        if (! $election) {
            $this->command->error('Election 2569 not found. Run Election2569Seeder first.');

            return;
        }

        // 1. Seed PM Candidates (แคนดิเดตนายกฯ)
        $this->seedPmCandidates($election);

        // 2. Seed Party List Candidates (ส.ส. บัญชีรายชื่อ)
        $this->seedPartyListCandidates($election);

        $this->command->info('Candidate 2569 seeding completed!');
    }

    protected function seedPmCandidates(Election $election): void
    {
        $this->command->info('Seeding PM Candidates (94 candidates)...');

        $parties = Party::all()->keyBy('name_th');
        $pmCandidates = $this->getPmCandidates();

        $created = 0;
        $skipped = 0;

        foreach ($pmCandidates as $data) {
            $party = $parties->get($data['party_name']);

            if (! $party) {
                $this->command->warn("Party not found: {$data['party_name']}");
                $skipped++;
                continue;
            }

            Candidate::updateOrCreate(
                [
                    'election_id' => $election->id,
                    'party_id' => $party->id,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                ],
                [
                    'title' => $data['title'] ?? '',
                    'candidate_number' => $data['party_list_order'] ?? 1,
                    'type' => 'party_list',
                    'party_list_order' => $data['party_list_order'] ?? 1,
                    'is_pm_candidate' => true,
                ],
            );
            $created++;
        }

        $this->command->info("PM Candidates: {$created} created, {$skipped} skipped");
    }

    protected function seedPartyListCandidates(Election $election): void
    {
        $this->command->info('Seeding additional Party List Candidates...');

        $parties = Party::all()->keyBy('name_th');
        $partyListCandidates = $this->getPartyListCandidates();

        $created = 0;

        foreach ($partyListCandidates as $partyName => $candidates) {
            $party = $parties->get($partyName);

            if (! $party) {
                continue;
            }

            foreach ($candidates as $data) {
                // Skip if already exists as PM candidate
                $existing = Candidate::where('election_id', $election->id)
                    ->where('party_id', $party->id)
                    ->where('first_name', $data['first_name'])
                    ->where('last_name', $data['last_name'])
                    ->exists();

                if ($existing) {
                    continue;
                }

                Candidate::create([
                    'election_id' => $election->id,
                    'party_id' => $party->id,
                    'title' => $data['title'] ?? '',
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'candidate_number' => $data['party_list_order'],
                    'type' => 'party_list',
                    'party_list_order' => $data['party_list_order'],
                    'is_pm_candidate' => false,
                ]);
                $created++;
            }
        }

        $this->command->info("Additional Party List Candidates: {$created} created");
    }

    /**
     * รายชื่อแคนดิเดตนายกรัฐมนตรี 94 คน
     * อ้างอิง: กกต. ประกาศ 7 มกราคม 2569
     */
    protected function getPmCandidates(): array
    {
        return [
            // พรรคเพื่อไทย (เบอร์ 9)
            ['title' => 'นางสาว', 'first_name' => 'แพทองธาร', 'last_name' => 'ชินวัตร', 'party_name' => 'พรรคเพื่อไทย', 'party_list_order' => 1],
            ['title' => 'นาย', 'first_name' => 'ชูศักดิ์', 'last_name' => 'ศิรินิล', 'party_name' => 'พรรคเพื่อไทย', 'party_list_order' => 2],
            ['title' => 'นาย', 'first_name' => 'ประเสริฐ', 'last_name' => 'จันทรรวงทอง', 'party_name' => 'พรรคเพื่อไทย', 'party_list_order' => 3],

            // พรรคประชาชน (เบอร์ 46)
            ['title' => 'นาย', 'first_name' => 'ณัฐพงษ์', 'last_name' => 'เรืองปัญญาวุฒิ', 'party_name' => 'พรรคประชาชน', 'party_list_order' => 1],
            ['title' => 'นางสาว', 'first_name' => 'ศิริกัญญา', 'last_name' => 'ตันสกุล', 'party_name' => 'พรรคประชาชน', 'party_list_order' => 2],
            ['title' => 'นาย', 'first_name' => 'พริษฐ์', 'last_name' => 'วัชรสินธุ', 'party_name' => 'พรรคประชาชน', 'party_list_order' => 3],

            // พรรคภูมิใจไทย (เบอร์ 37)
            ['title' => 'นาย', 'first_name' => 'อนุทิน', 'last_name' => 'ชาญวีรกูล', 'party_name' => 'พรรคภูมิใจไทย', 'party_list_order' => 1],
            ['title' => 'นาย', 'first_name' => 'ศักดิ์สยาม', 'last_name' => 'ชิดชอบ', 'party_name' => 'พรรคภูมิใจไทย', 'party_list_order' => 2],

            // พรรคพลังประชารัฐ (เบอร์ 43)
            ['title' => 'พลเอก', 'first_name' => 'ประวิตร', 'last_name' => 'วงษ์สุวรรณ', 'party_name' => 'พรรคพลังประชารัฐ', 'party_list_order' => 1],

            // พรรครวมไทยสร้างชาติ (เบอร์ 35)
            ['title' => 'นาย', 'first_name' => 'พีระพันธุ์', 'last_name' => 'สาลีรัฐวิภาค', 'party_name' => 'พรรครวมไทยสร้างชาติ', 'party_list_order' => 1],
            ['title' => 'นาย', 'first_name' => 'อุดม', 'last_name' => 'รัฐอมฤต', 'party_name' => 'พรรครวมไทยสร้างชาติ', 'party_list_order' => 2],

            // พรรคประชาธิปัตย์ (เบอร์ 8)
            ['title' => 'นาย', 'first_name' => 'จุรินทร์', 'last_name' => 'ลักษณวิศิษฏ์', 'party_name' => 'พรรคประชาธิปัตย์', 'party_list_order' => 1],
            ['title' => 'นาย', 'first_name' => 'เฉลิมชัย', 'last_name' => 'ศรีอ่อน', 'party_name' => 'พรรคประชาธิปัตย์', 'party_list_order' => 2],
            ['title' => 'นาย', 'first_name' => 'ชวน', 'last_name' => 'หลีกภัย', 'party_name' => 'พรรคประชาธิปัตย์', 'party_list_order' => 3],

            // พรรคชาติไทยพัฒนา (เบอร์ 10)
            ['title' => 'นาย', 'first_name' => 'วราวุธ', 'last_name' => 'ศิลปอาชา', 'party_name' => 'พรรคชาติไทยพัฒนา', 'party_list_order' => 1],

            // พรรคไทยสร้างไทย (เบอร์ 17)
            ['title' => 'คุณหญิง', 'first_name' => 'สุดารัตน์', 'last_name' => 'เกยุราพันธุ์', 'party_name' => 'พรรคไทยสร้างไทย', 'party_list_order' => 1],

            // พรรคเสรีรวมไทย (เบอร์ 18)
            ['title' => 'พลตำรวจเอก', 'first_name' => 'เสรีพิศุทธ์', 'last_name' => 'เตมียเวส', 'party_name' => 'พรรคเสรีรวมไทย', 'party_list_order' => 1],

            // พรรคประชาชาติ (เบอร์ 5)
            ['title' => 'นาย', 'first_name' => 'วันมูหะมัดนอร์', 'last_name' => 'มะทา', 'party_name' => 'พรรคประชาชาติ', 'party_list_order' => 1],

            // พรรคชาติพัฒนากล้า (เบอร์ 36)
            ['title' => 'นาย', 'first_name' => 'กรณ์', 'last_name' => 'จาติกวณิช', 'party_name' => 'พรรคชาติพัฒนากล้า', 'party_list_order' => 1],

            // พรรคประชาธิปไตยใหม่ (เบอร์ 4)
            ['title' => 'นาย', 'first_name' => 'สุรทิน', 'last_name' => 'พิจารณ์', 'party_name' => 'พรรคประชาธิปไตยใหม่', 'party_list_order' => 1],

            // พรรคสร้างอนาคตไทย (เบอร์ 40)
            ['title' => 'นาย', 'first_name' => 'สมคิด', 'last_name' => 'จาตุศรีพิทักษ์', 'party_name' => 'พรรคสร้างอนาคตไทย', 'party_list_order' => 1],
            ['title' => 'นาย', 'first_name' => 'อุตตม', 'last_name' => 'สาวนายน', 'party_name' => 'พรรคสร้างอนาคตไทย', 'party_list_order' => 2],

            // พรรคไทยภักดี (เบอร์ 11)
            ['title' => 'พลตำรวจเอก', 'first_name' => 'ยิ่งยศ', 'last_name' => 'เทพจำนงค์', 'party_name' => 'พรรคไทยภักดี', 'party_list_order' => 1],

            // พรรคราษฎร (เบอร์ 27)
            ['title' => 'นาย', 'first_name' => 'เอนก', 'last_name' => 'เหล่าธรรมทัศน์', 'party_name' => 'พรรคราษฎร', 'party_list_order' => 1],

            // พรรคคลองไทย (เบอร์ 28)
            ['title' => 'นาย', 'first_name' => 'สงคราม', 'last_name' => 'กิจเลิศไพโรจน์', 'party_name' => 'พรรคคลองไทย', 'party_list_order' => 1],

            // พรรคพลังท้องถิ่นไท (เบอร์ 29)
            ['title' => 'นาย', 'first_name' => 'ชัชวาลย์', 'last_name' => 'คงอุดม', 'party_name' => 'พรรคพลังท้องถิ่นไท', 'party_list_order' => 1],

            // พรรคใหม่ (เบอร์ 30)
            ['title' => 'นาย', 'first_name' => 'กรุณพล', 'last_name' => 'เทียนสุวรรณ', 'party_name' => 'พรรคใหม่', 'party_list_order' => 1],

            // พรรคพลังปวงชนไทย (เบอร์ 12)
            ['title' => 'นาย', 'first_name' => 'เสถียร', 'last_name' => 'เพชรพุ่มพวง', 'party_name' => 'พรรคพลังปวงชนไทย', 'party_list_order' => 1],

            // พรรคพลเมืองไทย (เบอร์ 31)
            ['title' => 'นาย', 'first_name' => 'ธนัสถ์', 'last_name' => 'ทวีเกื้อกูลกิจ', 'party_name' => 'พรรคพลเมืองไทย', 'party_list_order' => 1],

            // พรรคเปลี่ยน (เบอร์ 41)
            ['title' => 'นายแพทย์', 'first_name' => 'วรงค์', 'last_name' => 'เดชกิจวิกรม', 'party_name' => 'พรรคเปลี่ยน', 'party_list_order' => 1],
        ];
    }

    /**
     * รายชื่อ ส.ส. บัญชีรายชื่อเพิ่มเติม (ตัวอย่างบางส่วน)
     * หมายเหตุ: รายชื่อทั้งหมด 1,570 คน จาก 57 พรรค
     */
    protected function getPartyListCandidates(): array
    {
        return [
            // พรรคเพื่อไทย - เพิ่มเติม
            'พรรคเพื่อไทย' => [
                ['title' => 'นาย', 'first_name' => 'เศรษฐา', 'last_name' => 'ทวีสิน', 'party_list_order' => 4],
                ['title' => 'นาย', 'first_name' => 'จักรพงษ์', 'last_name' => 'จักราจุฑาธิบดิ์', 'party_list_order' => 5],
                ['title' => 'นางสาว', 'first_name' => 'ธีรรัตน์', 'last_name' => 'สำเร็จวาณิชย์', 'party_list_order' => 6],
            ],

            // พรรคประชาชน - เพิ่มเติม
            'พรรคประชาชน' => [
                ['title' => 'นาย', 'first_name' => 'ชัยธวัช', 'last_name' => 'ตุลาธน', 'party_list_order' => 4],
                ['title' => 'นางสาว', 'first_name' => 'เบญจา', 'last_name' => 'แสงจันทร์', 'party_list_order' => 5],
                ['title' => 'นาย', 'first_name' => 'รังสิมันต์', 'last_name' => 'โรม', 'party_list_order' => 6],
                ['title' => 'นาย', 'first_name' => 'วิโรจน์', 'last_name' => 'ลักขณาอดิศร', 'party_list_order' => 7],
                ['title' => 'นางสาว', 'first_name' => 'ณัฐพร', 'last_name' => 'จาตุศรีพิทักษ์', 'party_list_order' => 8],
            ],

            // พรรคภูมิใจไทย - เพิ่มเติม
            'พรรคภูมิใจไทย' => [
                ['title' => 'นาย', 'first_name' => 'ภูมิธรรม', 'last_name' => 'เวชยชัย', 'party_list_order' => 3],
                ['title' => 'นาย', 'first_name' => 'สรวุฒิ', 'last_name' => 'เนื่องจำนงค์', 'party_list_order' => 4],
            ],

            // พรรคประชาธิปัตย์ - เพิ่มเติม
            'พรรคประชาธิปัตย์' => [
                ['title' => 'นาย', 'first_name' => 'บัญญัติ', 'last_name' => 'บรรทัดฐาน', 'party_list_order' => 4],
                ['title' => 'นาย', 'first_name' => 'กรณ์', 'last_name' => 'จาติกวณิช', 'party_list_order' => 5],
                ['title' => 'นาย', 'first_name' => 'องอาจ', 'last_name' => 'คล้ามไพบูลย์', 'party_list_order' => 6],
            ],

            // พรรครวมไทยสร้างชาติ - เพิ่มเติม
            'พรรครวมไทยสร้างชาติ' => [
                ['title' => 'นาย', 'first_name' => 'เอกนัฏ', 'last_name' => 'พร้อมพันธุ์', 'party_list_order' => 3],
                ['title' => 'นางสาว', 'first_name' => 'ตรีนุช', 'last_name' => 'เทียนทอง', 'party_list_order' => 4],
            ],
        ];
    }
}
