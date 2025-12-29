// ข้อมูลพรรคการเมืองหลักของประเทศไทย (สำหรับการแสดงผลในระบบ)
// ข้อมูลอ้างอิงจาก กกต. 2566

export const parties = [
    {
        id: 1,
        name_th: 'พรรคก้าวไกล',
        name_en: 'Move Forward Party',
        abbreviation: 'ก้าวไกล',
        abbreviation_en: 'MFP',
        color: '#FF6B00',
        color_secondary: '#FF8C33',
        leader_name: 'พิธา ลิ้มเจริญรัตน์',
        founded_date: '2020-03-08',
        logo: '/images/parties/move-forward.png',
        website: 'https://moveforwardparty.org',
        policies: ['ปฏิรูปกองทัพ', 'กระจายอำนาจ', 'แก้ไขรัฐธรรมนูญ']
    },
    {
        id: 2,
        name_th: 'พรรคเพื่อไทย',
        name_en: 'Pheu Thai Party',
        abbreviation: 'เพื่อไทย',
        abbreviation_en: 'PT',
        color: '#E31E25',
        color_secondary: '#FF4D52',
        leader_name: 'แพทองธาร ชินวัตร',
        founded_date: '2008-09-20',
        logo: '/images/parties/pheu-thai.png',
        website: 'https://ptp.or.th',
        policies: ['เศรษฐกิจดิจิทัล', 'Digital Wallet', 'พลังงานสะอาด']
    },
    {
        id: 3,
        name_th: 'พรรคภูมิใจไทย',
        name_en: 'Bhumjaithai Party',
        abbreviation: 'ภูมิใจไทย',
        abbreviation_en: 'BJT',
        color: '#0066B3',
        color_secondary: '#3399CC',
        leader_name: 'อนุทิน ชาญวีรกูล',
        founded_date: '2008-11-05',
        logo: '/images/parties/bhumjaithai.png',
        website: 'https://www.bhumjaithai.com',
        policies: ['กัญชาเสรี', 'ท่องเที่ยวเชิงสุขภาพ', 'พัฒนาชนบท']
    },
    {
        id: 4,
        name_th: 'พรรคพลังประชารัฐ',
        name_en: 'Palang Pracharath Party',
        abbreviation: 'พลังประชารัฐ',
        abbreviation_en: 'PPRP',
        color: '#1E3A8A',
        color_secondary: '#3B5998',
        leader_name: 'ประวิตร วงษ์สุวรรณ',
        founded_date: '2018-03-02',
        logo: '/images/parties/pprp.png',
        website: 'https://www.pprp.or.th',
        policies: ['สานต่อโครงการรัฐ', 'บัตรสวัสดิการแห่งรัฐ']
    },
    {
        id: 5,
        name_th: 'พรรครวมไทยสร้างชาติ',
        name_en: 'United Thai Nation Party',
        abbreviation: 'รวมไทยสร้างชาติ',
        abbreviation_en: 'UTN',
        color: '#6B21A8',
        color_secondary: '#9333EA',
        leader_name: 'พลเอก ประยุทธ์ จันทร์โอชา',
        founded_date: '2021-03-26',
        logo: '/images/parties/utn.png',
        website: 'https://www.ruamthai.or.th',
        policies: ['ความมั่นคง', 'สานต่อโครงสร้างพื้นฐาน']
    },
    {
        id: 6,
        name_th: 'พรรคประชาธิปัตย์',
        name_en: 'Democrat Party',
        abbreviation: 'ประชาธิปัตย์',
        abbreviation_en: 'DP',
        color: '#00AEEF',
        color_secondary: '#66D3FF',
        leader_name: 'จุรินทร์ ลักษณวิศิษฏ์',
        founded_date: '1946-04-06',
        logo: '/images/parties/democrat.png',
        website: 'https://www.democrat.or.th',
        policies: ['เสรีนิยม', 'กระจายอำนาจ', 'ประกันสังคม']
    },
    {
        id: 7,
        name_th: 'พรรคชาติไทยพัฒนา',
        name_en: 'Chart Thai Pattana Party',
        abbreviation: 'ชาติไทยพัฒนา',
        abbreviation_en: 'CTP',
        color: '#8B4513',
        color_secondary: '#A0522D',
        leader_name: 'วราวุธ ศิลปอาชา',
        founded_date: '2008-04-18',
        logo: '/images/parties/ctp.png',
        website: null,
        policies: ['พัฒนาเกษตร', 'ท้องถิ่น']
    },
    {
        id: 8,
        name_th: 'พรรคประชาชาติ',
        name_en: 'Prachachat Party',
        abbreviation: 'ประชาชาติ',
        abbreviation_en: 'PC',
        color: '#00FF7F',
        color_secondary: '#32CD32',
        leader_name: 'วันมูหะมัดนอร์ มะทา',
        founded_date: '2018-08-24',
        logo: '/images/parties/prachachat.png',
        website: null,
        policies: ['มุสลิม', 'ชายแดนใต้']
    },
    {
        id: 9,
        name_th: 'พรรคไทยสร้างไทย',
        name_en: 'Thai Sang Thai Party',
        abbreviation: 'ไทยสร้างไทย',
        abbreviation_en: 'TST',
        color: '#FFC107',
        color_secondary: '#FFD54F',
        leader_name: 'คุณหญิงสุดารัตน์ เกยุราพันธุ์',
        founded_date: '2021-10-01',
        logo: '/images/parties/tst.png',
        website: 'https://thaisangthai.org',
        policies: ['พลังงานทดแทน', 'เกษตรยั่งยืน']
    },
    {
        id: 10,
        name_th: 'พรรคเสรีรวมไทย',
        name_en: 'Thai Liberal Party',
        abbreviation: 'เสรีรวมไทย',
        abbreviation_en: 'TLP',
        color: '#FF69B4',
        color_secondary: '#FF1493',
        leader_name: 'พลตำรวจเอก เสรีพิศุทธ์ เตมียเวส',
        founded_date: '2018-08-10',
        logo: '/images/parties/tlp.png',
        website: null,
        policies: ['ปราบคอร์รัปชัน', 'ปฏิรูปตำรวจ']
    }
];

// ฟังก์ชันหาพรรคจาก id
export const getPartyById = (id) => parties.find(p => p.id === id);

// ฟังก์ชันหาพรรคจากชื่อย่อ
export const getPartyByAbbreviation = (abbr) =>
    parties.find(p => p.abbreviation === abbr || p.abbreviation_en === abbr);

// สี default สำหรับพรรคที่ไม่มีข้อมูล
export const defaultPartyColor = '#9CA3AF';

// จำนวนพรรคทั้งหมดที่ลงเลือกตั้ง 2566 (อ้างอิง กกต.)
export const totalPartiesIn2023 = 70;

export default {
    parties,
    getPartyById,
    getPartyByAbbreviation,
    defaultPartyColor,
    totalPartiesIn2023
};
