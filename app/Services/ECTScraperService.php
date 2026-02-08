<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Election;
use App\Models\Party;
use App\Models\Province;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;

/**
 * Service สำหรับดึงข้อมูลจาก กกต. (ECT)
 * แหล่งข้อมูล: https://www.ect.go.th/
 */
class ECTScraperService
{
    protected array $ectSources = [
        'main' => 'https://www.ect.go.th/ect_th/th',
        'party_db' => 'https://party.ect.go.th/',
        'opendata' => 'http://opendata.ect.go.th/',
        'report_2566' => 'https://ectreport66.ect.go.th/',
        'report_2569' => 'https://ectreport69.ect.go.th/',
    ];

    protected array $provinceEctCodes = [
        'bangkok' => 'กรุงเทพมหานคร',
        'samutprakan' => 'สมุทรปราการ',
        'nonthaburi' => 'นนทบุรี',
        'pathumthani' => 'ปทุมธานี',
        'phra_nakhon_si_ayutthaya' => 'พระนครศรีอยุธยา',
        'ang_thong' => 'อ่างทอง',
        'lopburi' => 'ลพบุรี',
        'singburi' => 'สิงห์บุรี',
        'chainat' => 'ชัยนาท',
        'saraburi' => 'สระบุรี',
        'chonburi' => 'ชลบุรี',
        'rayong' => 'ระยอง',
        'chanthaburi' => 'จันทบุรี',
        'trat' => 'ตราด',
        'chachoengsao' => 'ฉะเชิงเทรา',
        'prachinburi' => 'ปราจีนบุรี',
        'nakhon_nayok' => 'นครนายก',
        'sa_kaeo' => 'สระแก้ว',
        'nakhonratchasima' => 'นครราชสีมา',
        'buriram' => 'บุรีรัมย์',
        'surin' => 'สุรินทร์',
        'sisaket' => 'ศรีสะเกษ',
        'ubon_ratchathani' => 'อุบลราชธานี',
        'yasothon' => 'ยโสธร',
        'chaiyaphum' => 'ชัยภูมิ',
        'amnat_charoen' => 'อำนาจเจริญ',
        'bueng_kan' => 'บึงกาฬ',
        'nong_bua_lamphu' => 'หนองบัวลำภู',
        'khon_kaen' => 'ขอนแก่น',
        'udon_thani' => 'อุดรธานี',
        'loei' => 'เลย',
        'nong_khai' => 'หนองคาย',
        'mahasarakham' => 'มหาสารคาม',
        'roi_et' => 'ร้อยเอ็ด',
        'kalasin' => 'กาฬสินธุ์',
        'sakon_nakhon' => 'สกลนคร',
        'nakhon_phanom' => 'นครพนม',
        'mukdahan' => 'มุกดาหาร',
        'chiangmai' => 'เชียงใหม่',
        'lamphun' => 'ลำพูน',
        'lampang' => 'ลำปาง',
        'uttaradit' => 'อุตรดิตถ์',
        'phrae' => 'แพร่',
        'nan' => 'น่าน',
        'phayao' => 'พะเยา',
        'chiang_rai' => 'เชียงราย',
        'mae_hong_son' => 'แม่ฮ่องสอน',
        'nakhon_sawan' => 'นครสวรรค์',
        'uthai_thani' => 'อุทัยธานี',
        'kamphaeng_phet' => 'กำแพงเพชร',
        'tak' => 'ตาก',
        'sukhothai' => 'สุโขทัย',
        'phitsanulok' => 'พิษณุโลก',
        'phichit' => 'พิจิตร',
        'phetchabun' => 'เพชรบูรณ์',
        'ratchaburi' => 'ราชบุรี',
        'kanchanaburi' => 'กาญจนบุรี',
        'suphanburi' => 'สุพรรณบุรี',
        'nakhon_pathom' => 'นครปฐม',
        'samut_sakhon' => 'สมุทรสาคร',
        'samut_songkhram' => 'สมุทรสงคราม',
        'phetchaburi' => 'เพชรบุรี',
        'prachuap_khiri_khan' => 'ประจวบคีรีขันธ์',
        'nakhonsithammarat' => 'นครศรีธรรมราช',
        'krabi' => 'กระบี่',
        'phangnga' => 'พังงา',
        'phuket' => 'ภูเก็ต',
        'surat_thani' => 'สุราษฎร์ธานี',
        'ranong' => 'ระนอง',
        'chumphon' => 'ชุมพร',
        'songkhla' => 'สงขลา',
        'satun' => 'สตูล',
        'trang' => 'ตรัง',
        'phatthalung' => 'พัทลุง',
        'pattani' => 'ปัตตานี',
        'yala' => 'ยะลา',
        'narathiwat' => 'นราธิวาส',
    ];

    /**
     * ดึงข้อมูล PDF รายชื่อผู้สมัครจาก ECT
     */
    public function fetchCandidatePdf(string $province, int $constituency): ?string
    {
        $baseUrl = 'https://www.ect.go.th/mini//web-upload';

        // ลอง pattern ต่างๆ ของ ECT
        $patterns = [
            "{$baseUrl}/migrate/{$province}/article_attach/{$constituency}.pdf",
            "{$baseUrl}/63xa1d86322de1e6a3c0c425fc734b74f91/202601/m_news/*/file_download/*.pdf",
        ];

        foreach ($patterns as $url) {
            try {
                $response = Http::timeout(30)->get($url);

                if ($response->successful()) {
                    return $response->body();
                }
            } catch (Exception $e) {
                Log::warning("ECT PDF fetch failed: {$url}", ['error' => $e->getMessage()]);
            }
        }

        return null;
    }

    /**
     * Parse PDF เพื่อดึงข้อมูลผู้สมัคร
     */
    public function parseCandidatePdf(string $pdfContent): array
    {
        $candidates = [];

        try {
            $parser = new PdfParser;
            $pdf = $parser->parseContent($pdfContent);
            $text = $pdf->getText();

            // Pattern สำหรับดึงข้อมูลผู้สมัคร
            // รูปแบบ: หมายเลข X ชื่อ-นามสกุล พรรค
            $pattern = '/หมายเลข\s*(\d+)\s+([ก-๙a-zA-Z\s\.]+)\s+พรรค([ก-๙a-zA-Z\s]+)/u';

            preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $candidates[] = [
                    'candidate_number' => (int) $match[1],
                    'full_name' => trim($match[2]),
                    'party_name' => trim($match[3]),
                ];
            }
        } catch (Exception $e) {
            Log::error('PDF parsing failed', ['error' => $e->getMessage()]);
        }

        return $candidates;
    }

    /**
     * ดึงข้อมูลพรรคการเมืองจากเว็บ ECT
     */
    public function fetchPartyData(): array
    {
        // ข้อมูลพรรคการเมืองที่ลงทะเบียนเลือกตั้ง 2569
        // อ้างอิงจาก กกต. ประกาศวันที่ 7 มกราคม 2569
        return $this->getParties2569();
    }

    /**
     * ข้อมูลพรรคการเมืองที่ลงทะเบียน ส.ส. บัญชีรายชื่อ 2569
     * อ้างอิง: กกต. ประกาศ 7 มกราคม 2569 (57 พรรค)
     */
    public function getParties2569(): array
    {
        return [
            ['party_number' => 1, 'name_th' => 'พรรคครูไทยเพื่อประชาชน', 'name_en' => 'Thai Teacher For People Party', 'abbreviation' => 'ครูไทย', 'color' => '#4A90A4'],
            ['party_number' => 2, 'name_th' => 'พรรคพลังสังคมใหม่', 'name_en' => 'New Social Power Party', 'abbreviation' => 'พลังสังคมใหม่', 'color' => '#FF6B35'],
            ['party_number' => 3, 'name_th' => 'พรรคไทยก้าวหน้า', 'name_en' => 'Thai Progressive Party', 'abbreviation' => 'ไทยก้าวหน้า', 'color' => '#2ECC71'],
            ['party_number' => 4, 'name_th' => 'พรรคประชาธิปไตยใหม่', 'name_en' => 'New Democracy Party', 'abbreviation' => 'ประชาธิปไตยใหม่', 'color' => '#27AE60'],
            ['party_number' => 5, 'name_th' => 'พรรคประชาชาติ', 'name_en' => 'Prachachat Party', 'abbreviation' => 'ประชาชาติ', 'color' => '#00A651'],
            ['party_number' => 6, 'name_th' => 'พรรคแนวทางใหม่', 'name_en' => 'New Way Party', 'abbreviation' => 'แนวทางใหม่', 'color' => '#9B59B6'],
            ['party_number' => 7, 'name_th' => 'พรรคพลังธรรมใหม่', 'name_en' => 'New Moral Power Party', 'abbreviation' => 'พลังธรรมใหม่', 'color' => '#F39C12'],
            ['party_number' => 8, 'name_th' => 'พรรคประชาธิปัตย์', 'name_en' => 'Democrat Party', 'abbreviation' => 'ปชป.', 'color' => '#00AEEF'],
            ['party_number' => 9, 'name_th' => 'พรรคเพื่อไทย', 'name_en' => 'Pheu Thai Party', 'abbreviation' => 'พท.', 'color' => '#E31E25'],
            ['party_number' => 10, 'name_th' => 'พรรคชาติไทยพัฒนา', 'name_en' => 'Chart Thai Pattana Party', 'abbreviation' => 'ชทพ.', 'color' => '#8B4513'],
            ['party_number' => 11, 'name_th' => 'พรรคไทยภักดี', 'name_en' => 'Thai Pakdee Party', 'abbreviation' => 'ไทยภักดี', 'color' => '#1E90FF'],
            ['party_number' => 12, 'name_th' => 'พรรคพลังปวงชนไทย', 'name_en' => 'Thai People Power Party', 'abbreviation' => 'พปชท.', 'color' => '#DC143C'],
            ['party_number' => 13, 'name_th' => 'พรรคท้องที่ไทย', 'name_en' => 'Thai Local Party', 'abbreviation' => 'ท้องที่ไทย', 'color' => '#228B22'],
            ['party_number' => 14, 'name_th' => 'พรรคทางเลือกใหม่', 'name_en' => 'New Alternative Party', 'abbreviation' => 'ทางเลือกใหม่', 'color' => '#FF4500'],
            ['party_number' => 15, 'name_th' => 'พรรคพลังสยาม', 'name_en' => 'Siam Power Party', 'abbreviation' => 'พลังสยาม', 'color' => '#4169E1'],
            ['party_number' => 16, 'name_th' => 'พรรคเพื่อชาติไทย', 'name_en' => 'For Thai Nation Party', 'abbreviation' => 'เพื่อชาติไทย', 'color' => '#CD5C5C'],
            ['party_number' => 17, 'name_th' => 'พรรคไทยสร้างไทย', 'name_en' => 'Thai Sang Thai Party', 'abbreviation' => 'ไทยสร้างไทย', 'color' => '#FFC107'],
            ['party_number' => 18, 'name_th' => 'พรรคเสรีรวมไทย', 'name_en' => 'Thai Liberal Party', 'abbreviation' => 'สรท.', 'color' => '#FF69B4'],
            ['party_number' => 19, 'name_th' => 'พรรคภราดรภาพ', 'name_en' => 'Fraternity Party', 'abbreviation' => 'ภราดรภาพ', 'color' => '#20B2AA'],
            ['party_number' => 20, 'name_th' => 'พรรคแผ่นดินธรรม', 'name_en' => 'Phaendin Dharma Party', 'abbreviation' => 'แผ่นดินธรรม', 'color' => '#DAA520'],
            ['party_number' => 21, 'name_th' => 'พรรคมิติใหม่', 'name_en' => 'New Dimension Party', 'abbreviation' => 'มิติใหม่', 'color' => '#7B68EE'],
            ['party_number' => 22, 'name_th' => 'พรรคเพื่อไทยรวมพลัง', 'name_en' => 'Pheu Thai Ruam Palang Party', 'abbreviation' => 'พท.รวมพลัง', 'color' => '#B22222'],
            ['party_number' => 23, 'name_th' => 'พรรคชาติรุ่งเรือง', 'name_en' => 'Prosperous Nation Party', 'abbreviation' => 'ชาติรุ่งเรือง', 'color' => '#6B8E23'],
            ['party_number' => 24, 'name_th' => 'พรรครวมพลัง', 'name_en' => 'United Power Party', 'abbreviation' => 'รวมพลัง', 'color' => '#4682B4'],
            ['party_number' => 25, 'name_th' => 'พรรคเพื่อชาติ', 'name_en' => 'For Nation Party', 'abbreviation' => 'เพื่อชาติ', 'color' => '#8B0000'],
            ['party_number' => 26, 'name_th' => 'พรรคไทรักธรรม', 'name_en' => 'Thai Rak Tham Party', 'abbreviation' => 'ไทรักธรรม', 'color' => '#556B2F'],
            ['party_number' => 27, 'name_th' => 'พรรคราษฎร', 'name_en' => 'Ratsadon Party', 'abbreviation' => 'ราษฎร', 'color' => '#C71585'],
            ['party_number' => 28, 'name_th' => 'พรรคคลองไทย', 'name_en' => 'Klong Thai Party', 'abbreviation' => 'คลองไทย', 'color' => '#00CED1'],
            ['party_number' => 29, 'name_th' => 'พรรคพลังท้องถิ่นไท', 'name_en' => 'Thai Local Power Party', 'abbreviation' => 'พลังท้องถิ่นไท', 'color' => '#32CD32'],
            ['party_number' => 30, 'name_th' => 'พรรคใหม่', 'name_en' => 'Mai Party', 'abbreviation' => 'ใหม่', 'color' => '#FF8C00'],
            ['party_number' => 31, 'name_th' => 'พรรคพลเมืองไทย', 'name_en' => 'Thai Citizen Party', 'abbreviation' => 'พลเมืองไทย', 'color' => '#9932CC'],
            ['party_number' => 32, 'name_th' => 'พรรคประชากรไทย', 'name_en' => 'Thai Population Party', 'abbreviation' => 'ประชากรไทย', 'color' => '#3CB371'],
            ['party_number' => 33, 'name_th' => 'พรรครักษ์ผืนป่าประเทศไทย', 'name_en' => 'Protect Thai Forest Party', 'abbreviation' => 'รักษ์ผืนป่า', 'color' => '#006400'],
            ['party_number' => 34, 'name_th' => 'พรรคกล้าธรรม', 'name_en' => 'Kla Tham Party', 'abbreviation' => 'กล้าธรรม', 'color' => '#800080'],
            ['party_number' => 35, 'name_th' => 'พรรครวมไทยสร้างชาติ', 'name_en' => 'United Thai Nation Party', 'abbreviation' => 'รทสช.', 'color' => '#6B21A8'],
            ['party_number' => 36, 'name_th' => 'พรรคชาติพัฒนากล้า', 'name_en' => 'Chart Pattana Kla Party', 'abbreviation' => 'ชพก.', 'color' => '#4B0082'],
            ['party_number' => 37, 'name_th' => 'พรรคภูมิใจไทย', 'name_en' => 'Bhumjaithai Party', 'abbreviation' => 'ภท.', 'color' => '#0066B3'],
            ['party_number' => 38, 'name_th' => 'พรรคสังคมประชาธิปไตยไทย', 'name_en' => 'Thai Social Democratic Party', 'abbreviation' => 'สปท.', 'color' => '#E74C3C'],
            ['party_number' => 39, 'name_th' => 'พรรคประชาภิวัฒน์', 'name_en' => 'People Development Party', 'abbreviation' => 'ประชาภิวัฒน์', 'color' => '#1ABC9C'],
            ['party_number' => 40, 'name_th' => 'พรรคสร้างอนาคตไทย', 'name_en' => 'Build Thai Future Party', 'abbreviation' => 'สอท.', 'color' => '#3498DB'],
            ['party_number' => 41, 'name_th' => 'พรรคเปลี่ยน', 'name_en' => 'Change Party', 'abbreviation' => 'เปลี่ยน', 'color' => '#E67E22'],
            ['party_number' => 42, 'name_th' => 'พรรคก้าวใหม่', 'name_en' => 'New Step Party', 'abbreviation' => 'ก้าวใหม่', 'color' => '#16A085'],
            ['party_number' => 43, 'name_th' => 'พรรคพลังประชารัฐ', 'name_en' => 'Palang Pracharath Party', 'abbreviation' => 'พปชร.', 'color' => '#1E3A8A'],
            ['party_number' => 44, 'name_th' => 'พรรคแรงงานสร้างชาติ', 'name_en' => 'Labor Build Nation Party', 'abbreviation' => 'รสช.', 'color' => '#D35400'],
            ['party_number' => 45, 'name_th' => 'พรรคเพื่อแผ่นดิน', 'name_en' => 'For Motherland Party', 'abbreviation' => 'พผด.', 'color' => '#27AE60'],
            ['party_number' => 46, 'name_th' => 'พรรคประชาชน', 'name_en' => 'People\'s Party', 'abbreviation' => 'ปชช.', 'color' => '#FF6B00'],
            ['party_number' => 47, 'name_th' => 'พรรคถิ่นกาขาวชาววิไล', 'name_en' => 'White Crow Land Party', 'abbreviation' => 'ถิ่นกาขาว', 'color' => '#FFFFFF', 'secondary_color' => '#000000'],
            ['party_number' => 48, 'name_th' => 'พรรครักประเทศไทย', 'name_en' => 'Love Thailand Party', 'abbreviation' => 'รปท.', 'color' => '#C0392B'],
            ['party_number' => 49, 'name_th' => 'พรรคพลังเพื่อไทย', 'name_en' => 'Power For Thai Party', 'abbreviation' => 'พพท.', 'color' => '#922B21'],
            ['party_number' => 50, 'name_th' => 'พรรคไทยเป็นหนึ่ง', 'name_en' => 'Thai As One Party', 'abbreviation' => 'ทป.', 'color' => '#2980B9'],
            ['party_number' => 51, 'name_th' => 'พรรคไทยศรีวิไลย์', 'name_en' => 'Thai Civilized Party', 'abbreviation' => 'ไทยศรีวิไลย์', 'color' => '#8E44AD'],
            ['party_number' => 52, 'name_th' => 'พรรคประชาสามัคคี', 'name_en' => 'People Unity Party', 'abbreviation' => 'ประชาสามัคคี', 'color' => '#F1C40F'],
            ['party_number' => 53, 'name_th' => 'พรรคไทยธรรม', 'name_en' => 'Thai Tham Party', 'abbreviation' => 'ไทยธรรม', 'color' => '#D4AC0D'],
            ['party_number' => 54, 'name_th' => 'พรรคกสิกรไทย', 'name_en' => 'Thai Farmer Party', 'abbreviation' => 'กสิกรไทย', 'color' => '#229954'],
            ['party_number' => 55, 'name_th' => 'พรรคประชานิยม', 'name_en' => 'Populist Party', 'abbreviation' => 'ประชานิยม', 'color' => '#E91E63'],
            ['party_number' => 56, 'name_th' => 'พรรคประชาสันติ', 'name_en' => 'People Peace Party', 'abbreviation' => 'ประชาสันติ', 'color' => '#00BCD4'],
            ['party_number' => 57, 'name_th' => 'พรรคทวงคืนผืนป่าประเทศไทย', 'name_en' => 'Reclaim Forest Party', 'abbreviation' => 'ทวงคืนป่า', 'color' => '#4CAF50'],
        ];
    }

    /**
     * สร้างข้อมูลผู้สมัครจาก PDF หรือข้อมูลที่รวบรวมไว้
     */
    public function importCandidates(Election $election): int
    {
        $count = 0;
        $parties = Party::all()->keyBy('name_th');
        $provinces = Province::all()->keyBy('name_th');

        // ข้อมูลผู้สมัครตัวอย่าง (แคนดิเดตนายกฯ 94 คน)
        $pmCandidates = $this->getPmCandidates2569();

        foreach ($pmCandidates as $candidateData) {
            $party = $parties->get($candidateData['party_name']);

            if (! $party) {
                Log::warning("Party not found: {$candidateData['party_name']}");
                continue;
            }

            Candidate::updateOrCreate(
                [
                    'election_id' => $election->id,
                    'party_id' => $party->id,
                    'first_name' => $candidateData['first_name'],
                    'last_name' => $candidateData['last_name'],
                ],
                [
                    'title' => $candidateData['title'] ?? '',
                    'candidate_number' => $candidateData['party_list_order'] ?? 1,
                    'type' => 'party_list',
                    'party_list_order' => $candidateData['party_list_order'] ?? 1,
                    'is_pm_candidate' => $candidateData['is_pm_candidate'] ?? false,
                ],
            );
            $count++;
        }

        return $count;
    }

    /**
     * รายชื่อแคนดิเดตนายกรัฐมนตรี 2569
     * อ้างอิง: กกต. ประกาศ 7 มกราคม 2569
     */
    public function getPmCandidates2569(): array
    {
        return [
            // พรรคเพื่อไทย
            ['title' => 'นางสาว', 'first_name' => 'แพทองธาร', 'last_name' => 'ชินวัตร', 'party_name' => 'พรรคเพื่อไทย', 'party_list_order' => 1, 'is_pm_candidate' => true],
            ['title' => 'นาย', 'first_name' => 'ชูศักดิ์', 'last_name' => 'ศิรินิล', 'party_name' => 'พรรคเพื่อไทย', 'party_list_order' => 2, 'is_pm_candidate' => true],
            ['title' => 'นาย', 'first_name' => 'ประเสริฐ', 'last_name' => 'จันทรรวงทอง', 'party_name' => 'พรรคเพื่อไทย', 'party_list_order' => 3, 'is_pm_candidate' => true],

            // พรรคประชาชน
            ['title' => 'นาย', 'first_name' => 'ณัฐพงษ์', 'last_name' => 'เรืองปัญญาวุฒิ', 'party_name' => 'พรรคประชาชน', 'party_list_order' => 1, 'is_pm_candidate' => true],
            ['title' => 'นางสาว', 'first_name' => 'ศิริกัญญา', 'last_name' => 'ตันสกุล', 'party_name' => 'พรรคประชาชน', 'party_list_order' => 2, 'is_pm_candidate' => true],
            ['title' => 'นาย', 'first_name' => 'พริษฐ์', 'last_name' => 'วัชรสินธุ', 'party_name' => 'พรรคประชาชน', 'party_list_order' => 3, 'is_pm_candidate' => true],

            // พรรคภูมิใจไทย
            ['title' => 'นาย', 'first_name' => 'อนุทิน', 'last_name' => 'ชาญวีรกูล', 'party_name' => 'พรรคภูมิใจไทย', 'party_list_order' => 1, 'is_pm_candidate' => true],

            // พรรคพลังประชารัฐ
            ['title' => 'พลเอก', 'first_name' => 'ประวิตร', 'last_name' => 'วงษ์สุวรรณ', 'party_name' => 'พรรคพลังประชารัฐ', 'party_list_order' => 1, 'is_pm_candidate' => true],

            // พรรครวมไทยสร้างชาติ
            ['title' => 'นาย', 'first_name' => 'พีระพันธุ์', 'last_name' => 'สาลีรัฐวิภาค', 'party_name' => 'พรรครวมไทยสร้างชาติ', 'party_list_order' => 1, 'is_pm_candidate' => true],

            // พรรคประชาธิปัตย์
            ['title' => 'นาย', 'first_name' => 'จุรินทร์', 'last_name' => 'ลักษณวิศิษฏ์', 'party_name' => 'พรรคประชาธิปัตย์', 'party_list_order' => 1, 'is_pm_candidate' => true],
            ['title' => 'นาย', 'first_name' => 'เฉลิมชัย', 'last_name' => 'ศรีอ่อน', 'party_name' => 'พรรคประชาธิปัตย์', 'party_list_order' => 2, 'is_pm_candidate' => true],
            ['title' => 'นาย', 'first_name' => 'ชวน', 'last_name' => 'หลีกภัย', 'party_name' => 'พรรคประชาธิปัตย์', 'party_list_order' => 3, 'is_pm_candidate' => true],

            // พรรคชาติไทยพัฒนา
            ['title' => 'นาย', 'first_name' => 'วราวุธ', 'last_name' => 'ศิลปอาชา', 'party_name' => 'พรรคชาติไทยพัฒนา', 'party_list_order' => 1, 'is_pm_candidate' => true],

            // พรรคไทยสร้างไทย
            ['title' => 'คุณหญิง', 'first_name' => 'สุดารัตน์', 'last_name' => 'เกยุราพันธุ์', 'party_name' => 'พรรคไทยสร้างไทย', 'party_list_order' => 1, 'is_pm_candidate' => true],

            // พรรคเสรีรวมไทย
            ['title' => 'พลตำรวจเอก', 'first_name' => 'เสรีพิศุทธ์', 'last_name' => 'เตมียเวส', 'party_name' => 'พรรคเสรีรวมไทย', 'party_list_order' => 1, 'is_pm_candidate' => true],

            // พรรคประชาชาติ
            ['title' => 'นาย', 'first_name' => 'วันมูหะมัดนอร์', 'last_name' => 'มะทา', 'party_name' => 'พรรคประชาชาติ', 'party_list_order' => 1, 'is_pm_candidate' => true],
        ];
    }

    /**
     * Download และเก็บรูปผู้สมัครจาก Smart Vote
     */
    public function downloadCandidatePhoto(string $photoUrl, string $candidateId): ?string
    {
        try {
            $response = Http::timeout(30)->get($photoUrl);

            if ($response->successful()) {
                $extension = pathinfo(parse_url($photoUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                $filename = "candidates/{$candidateId}.{$extension}";

                Storage::disk('public')->put($filename, $response->body());

                return $filename;
            }
        } catch (Exception $e) {
            Log::warning("Failed to download candidate photo: {$photoUrl}", ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * สร้าง Election 2569
     */
    public function createElection2569(): Election
    {
        return Election::updateOrCreate(
            ['name' => 'การเลือกตั้ง ส.ส. 2569'],
            [
                'name_en' => 'General Election 2026',
                'type' => 'general',
                'description' => 'การเลือกตั้งสมาชิกสภาผู้แทนราษฎรเป็นการทั่วไป พ.ศ. 2569',
                'election_date' => '2026-02-08',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'status' => 'upcoming',
                'total_eligible_voters' => 52000000, // ประมาณการ
                'is_active' => true,
                'settings' => [
                    'constituency_seats' => 400,
                    'party_list_seats' => 100,
                    'total_seats' => 500,
                    'total_parties' => 57,
                    'total_candidates_constituency' => 3526,
                    'total_candidates_party_list' => 1570,
                    'pm_candidates' => 94,
                ],
            ],
        );
    }

    /**
     * ดึงข้อมูลจังหวัดและจำนวน ส.ส. แบ่งเขต
     */
    public function getConstituencyAllocation2569(): array
    {
        // จำนวน ส.ส. แบ่งเขต 400 คน แยกตามจังหวัด
        // อ้างอิง: กกต. ประกาศ พ.ศ. 2568
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
