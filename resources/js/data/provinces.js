// ข้อมูล 77 จังหวัดของประเทศไทย พร้อมจำนวนเขตเลือกตั้ง 400 เขต (ตามข้อมูล กกต. 2566)
// SVG paths สำหรับแผนที่ประเทศไทย

export const regions = {
    north: { name_th: 'ภาคเหนือ', name_en: 'Northern', color: '#22C55E' },
    northeast: { name_th: 'ภาคตะวันออกเฉียงเหนือ', name_en: 'Northeastern', color: '#EF4444' },
    central: { name_th: 'ภาคกลาง', name_en: 'Central', color: '#F59E0B' },
    east: { name_th: 'ภาคตะวันออก', name_en: 'Eastern', color: '#3B82F6' },
    west: { name_th: 'ภาคตะวันตก', name_en: 'Western', color: '#8B5CF6' },
    south: { name_th: 'ภาคใต้', name_en: 'Southern', color: '#EC4899' },
};

// ข้อมูลจังหวัดทั้ง 77 จังหวัด พร้อมจำนวนเขตเลือกตั้ง (ตาม กกต. 2566)
export const provinces = [
    // ภาคเหนือ (9 จังหวัด)
    { id: 1, code: '50', name_th: 'เชียงใหม่', name_en: 'Chiang Mai', region: 'north', constituencies: 10, population: 1779954 },
    { id: 2, code: '51', name_th: 'ลำพูน', name_en: 'Lamphun', region: 'north', constituencies: 2, population: 404560 },
    { id: 3, code: '52', name_th: 'ลำปาง', name_en: 'Lampang', region: 'north', constituencies: 4, population: 729658 },
    { id: 4, code: '53', name_th: 'อุตรดิตถ์', name_en: 'Uttaradit', region: 'north', constituencies: 3, population: 454260 },
    { id: 5, code: '54', name_th: 'แพร่', name_en: 'Phrae', region: 'north', constituencies: 3, population: 436103 },
    { id: 6, code: '55', name_th: 'น่าน', name_en: 'Nan', region: 'north', constituencies: 3, population: 479431 },
    { id: 7, code: '56', name_th: 'พะเยา', name_en: 'Phayao', region: 'north', constituencies: 3, population: 471081 },
    { id: 8, code: '57', name_th: 'เชียงราย', name_en: 'Chiang Rai', region: 'north', constituencies: 7, population: 1285817 },
    { id: 9, code: '58', name_th: 'แม่ฮ่องสอน', name_en: 'Mae Hong Son', region: 'north', constituencies: 1, population: 285586 },

    // ภาคตะวันออกเฉียงเหนือ (20 จังหวัด) - 133 เขต
    { id: 10, code: '30', name_th: 'นครราชสีมา', name_en: 'Nakhon Ratchasima', region: 'northeast', constituencies: 16, population: 2634154 },
    { id: 11, code: '31', name_th: 'บุรีรัมย์', name_en: 'Buri Ram', region: 'northeast', constituencies: 10, population: 1595065 },
    { id: 12, code: '32', name_th: 'สุรินทร์', name_en: 'Surin', region: 'northeast', constituencies: 8, population: 1388359 },
    { id: 13, code: '33', name_th: 'ศรีสะเกษ', name_en: 'Si Sa Ket', region: 'northeast', constituencies: 9, population: 1463615 },
    { id: 14, code: '34', name_th: 'อุบลราชธานี', name_en: 'Ubon Ratchathani', region: 'northeast', constituencies: 11, population: 1878146 },
    { id: 15, code: '35', name_th: 'ยโสธร', name_en: 'Yasothon', region: 'northeast', constituencies: 3, population: 536018 },
    { id: 16, code: '36', name_th: 'ชัยภูมิ', name_en: 'Chaiyaphum', region: 'northeast', constituencies: 7, population: 1127423 },
    { id: 17, code: '37', name_th: 'อำนาจเจริญ', name_en: 'Amnat Charoen', region: 'northeast', constituencies: 2, population: 375689 },
    { id: 18, code: '38', name_th: 'บึงกาฬ', name_en: 'Bueng Kan', region: 'northeast', constituencies: 2, population: 422091 },
    { id: 19, code: '39', name_th: 'หนองบัวลำภู', name_en: 'Nong Bua Lam Phu', region: 'northeast', constituencies: 3, population: 509834 },
    { id: 20, code: '40', name_th: 'ขอนแก่น', name_en: 'Khon Kaen', region: 'northeast', constituencies: 11, population: 1792905 },
    { id: 21, code: '41', name_th: 'อุดรธานี', name_en: 'Udon Thani', region: 'northeast', constituencies: 10, population: 1570500 },
    { id: 22, code: '42', name_th: 'เลย', name_en: 'Loei', region: 'northeast', constituencies: 4, population: 637624 },
    { id: 23, code: '43', name_th: 'หนองคาย', name_en: 'Nong Khai', region: 'northeast', constituencies: 3, population: 517260 },
    { id: 24, code: '44', name_th: 'มหาสารคาม', name_en: 'Maha Sarakham', region: 'northeast', constituencies: 6, population: 960359 },
    { id: 25, code: '45', name_th: 'ร้อยเอ็ด', name_en: 'Roi Et', region: 'northeast', constituencies: 8, population: 1306104 },
    { id: 26, code: '46', name_th: 'กาฬสินธุ์', name_en: 'Kalasin', region: 'northeast', constituencies: 6, population: 983720 },
    { id: 27, code: '47', name_th: 'สกลนคร', name_en: 'Sakon Nakhon', region: 'northeast', constituencies: 7, population: 1148052 },
    { id: 28, code: '48', name_th: 'นครพนม', name_en: 'Nakhon Phanom', region: 'northeast', constituencies: 4, population: 714740 },
    { id: 29, code: '49', name_th: 'มุกดาหาร', name_en: 'Mukdahan', region: 'northeast', constituencies: 2, population: 352837 },

    // ภาคกลาง (22 จังหวัด รวม กทม.) - 122 เขต
    { id: 30, code: '10', name_th: 'กรุงเทพมหานคร', name_en: 'Bangkok', region: 'central', constituencies: 33, population: 5494910 },
    { id: 31, code: '11', name_th: 'สมุทรปราการ', name_en: 'Samut Prakan', region: 'central', constituencies: 8, population: 1349263 },
    { id: 32, code: '12', name_th: 'นนทบุรี', name_en: 'Nonthaburi', region: 'central', constituencies: 8, population: 1278540 },
    { id: 33, code: '13', name_th: 'ปทุมธานี', name_en: 'Pathum Thani', region: 'central', constituencies: 7, population: 1176412 },
    { id: 34, code: '14', name_th: 'พระนครศรีอยุธยา', name_en: 'Phra Nakhon Si Ayutthaya', region: 'central', constituencies: 5, population: 815384 },
    { id: 35, code: '15', name_th: 'อ่างทอง', name_en: 'Ang Thong', region: 'central', constituencies: 2, population: 280115 },
    { id: 36, code: '16', name_th: 'ลพบุรี', name_en: 'Lop Buri', region: 'central', constituencies: 4, population: 753828 },
    { id: 37, code: '17', name_th: 'สิงห์บุรี', name_en: 'Sing Buri', region: 'central', constituencies: 1, population: 206804 },
    { id: 38, code: '18', name_th: 'ชัยนาท', name_en: 'Chai Nat', region: 'central', constituencies: 2, population: 326949 },
    { id: 39, code: '19', name_th: 'สระบุรี', name_en: 'Saraburi', region: 'central', constituencies: 4, population: 645641 },
    { id: 40, code: '60', name_th: 'นครสวรรค์', name_en: 'Nakhon Sawan', region: 'central', constituencies: 6, population: 1059887 },
    { id: 41, code: '61', name_th: 'อุทัยธานี', name_en: 'Uthai Thani', region: 'central', constituencies: 2, population: 328292 },
    { id: 42, code: '62', name_th: 'กำแพงเพชร', name_en: 'Kamphaeng Phet', region: 'central', constituencies: 4, population: 725853 },
    { id: 43, code: '63', name_th: 'ตาก', name_en: 'Tak', region: 'central', constituencies: 3, population: 664450 },
    { id: 44, code: '64', name_th: 'สุโขทัย', name_en: 'Sukhothai', region: 'central', constituencies: 4, population: 597207 },
    { id: 45, code: '65', name_th: 'พิษณุโลก', name_en: 'Phitsanulok', region: 'central', constituencies: 5, population: 865368 },
    { id: 46, code: '66', name_th: 'พิจิตร', name_en: 'Phichit', region: 'central', constituencies: 3, population: 539529 },
    { id: 47, code: '67', name_th: 'เพชรบูรณ์', name_en: 'Phetchabun', region: 'central', constituencies: 6, population: 993693 },
    { id: 48, code: '72', name_th: 'สุพรรณบุรี', name_en: 'Suphan Buri', region: 'central', constituencies: 5, population: 842123 },
    { id: 49, code: '73', name_th: 'นครปฐม', name_en: 'Nakhon Pathom', region: 'central', constituencies: 6, population: 926732 },
    { id: 50, code: '74', name_th: 'สมุทรสาคร', name_en: 'Samut Sakhon', region: 'central', constituencies: 3, population: 579113 },
    { id: 51, code: '75', name_th: 'สมุทรสงคราม', name_en: 'Samut Songkhram', region: 'central', constituencies: 1, population: 193729 },

    // ภาคตะวันออก (7 จังหวัด)
    { id: 52, code: '20', name_th: 'ชลบุรี', name_en: 'Chon Buri', region: 'east', constituencies: 9, population: 1558301 },
    { id: 53, code: '21', name_th: 'ระยอง', name_en: 'Rayong', region: 'east', constituencies: 4, population: 742323 },
    { id: 54, code: '22', name_th: 'จันทบุรี', name_en: 'Chanthaburi', region: 'east', constituencies: 3, population: 540221 },
    { id: 55, code: '23', name_th: 'ตราด', name_en: 'Trat', region: 'east', constituencies: 1, population: 229235 },
    { id: 56, code: '24', name_th: 'ฉะเชิงเทรา', name_en: 'Chachoengsao', region: 'east', constituencies: 4, population: 716689 },
    { id: 57, code: '25', name_th: 'ปราจีนบุรี', name_en: 'Prachin Buri', region: 'east', constituencies: 3, population: 487026 },
    { id: 58, code: '26', name_th: 'นครนายก', name_en: 'Nakhon Nayok', region: 'east', constituencies: 2, population: 260654 },
    { id: 59, code: '27', name_th: 'สระแก้ว', name_en: 'Sa Kaeo', region: 'east', constituencies: 3, population: 564615 },

    // ภาคตะวันตก (5 จังหวัด)
    { id: 60, code: '70', name_th: 'ราชบุรี', name_en: 'Ratchaburi', region: 'west', constituencies: 5, population: 870913 },
    { id: 61, code: '71', name_th: 'กาญจนบุรี', name_en: 'Kanchanaburi', region: 'west', constituencies: 5, population: 898158 },
    { id: 62, code: '76', name_th: 'เพชรบุรี', name_en: 'Phetchaburi', region: 'west', constituencies: 3, population: 483448 },
    { id: 63, code: '77', name_th: 'ประจวบคีรีขันธ์', name_en: 'Prachuap Khiri Khan', region: 'west', constituencies: 3, population: 547338 },

    // ภาคใต้ (14 จังหวัด) - 60 เขต
    { id: 64, code: '80', name_th: 'นครศรีธรรมราช', name_en: 'Nakhon Si Thammarat', region: 'south', constituencies: 10, population: 1563637 },
    { id: 65, code: '81', name_th: 'กระบี่', name_en: 'Krabi', region: 'south', constituencies: 3, population: 479541 },
    { id: 66, code: '82', name_th: 'พังงา', name_en: 'Phang Nga', region: 'south', constituencies: 2, population: 270320 },
    { id: 67, code: '83', name_th: 'ภูเก็ต', name_en: 'Phuket', region: 'south', constituencies: 3, population: 418864 },
    { id: 68, code: '84', name_th: 'สุราษฎร์ธานี', name_en: 'Surat Thani', region: 'south', constituencies: 6, population: 1067846 },
    { id: 69, code: '85', name_th: 'ระนอง', name_en: 'Ranong', region: 'south', constituencies: 1, population: 192887 },
    { id: 70, code: '86', name_th: 'ชุมพร', name_en: 'Chumphon', region: 'south', constituencies: 3, population: 509650 },
    { id: 71, code: '90', name_th: 'สงขลา', name_en: 'Songkhla', region: 'south', constituencies: 9, population: 1439713 },
    { id: 72, code: '91', name_th: 'สตูล', name_en: 'Satun', region: 'south', constituencies: 2, population: 320294 },
    { id: 73, code: '92', name_th: 'ตรัง', name_en: 'Trang', region: 'south', constituencies: 4, population: 641510 },
    { id: 74, code: '93', name_th: 'พัทลุง', name_en: 'Phatthalung', region: 'south', constituencies: 3, population: 525295 },
    { id: 75, code: '94', name_th: 'ปัตตานี', name_en: 'Pattani', region: 'south', constituencies: 4, population: 727541 },
    { id: 76, code: '95', name_th: 'ยะลา', name_en: 'Yala', region: 'south', constituencies: 3, population: 533557 },
    { id: 77, code: '96', name_th: 'นราธิวาส', name_en: 'Narathiwat', region: 'south', constituencies: 4, population: 813445 },
];

// คำนวณจำนวนเขตรวมทั้งหมด
export const totalConstituencies = provinces.reduce((sum, p) => sum + p.constituencies, 0);

// SVG Paths สำหรับแผนที่ประเทศไทย (Simplified)
// ViewBox: 0 0 500 750
export const provincePaths = {
    // ภาคเหนือ
    '50': 'M180,80 L220,70 L250,90 L260,130 L240,170 L200,180 L160,150 L150,110 Z', // เชียงใหม่
    '51': 'M200,180 L240,170 L250,200 L220,220 L190,210 Z', // ลำพูน
    '52': 'M220,220 L270,200 L300,230 L280,280 L230,270 L200,240 Z', // ลำปาง
    '53': 'M280,280 L320,260 L350,300 L330,350 L290,340 L270,300 Z', // อุตรดิตถ์
    '54': 'M230,270 L280,280 L270,320 L230,330 L210,300 Z', // แพร่
    '55': 'M250,90 L300,80 L330,120 L320,180 L270,170 L260,130 Z', // น่าน
    '56': 'M270,170 L320,180 L310,230 L270,240 L250,200 Z', // พะเยา
    '57': 'M220,70 L280,50 L320,70 L330,120 L300,80 L250,90 Z', // เชียงราย
    '58': 'M100,90 L150,70 L180,80 L160,150 L120,160 L90,130 Z', // แม่ฮ่องสอน

    // ภาคตะวันออกเฉียงเหนือ
    '30': 'M320,380 L380,360 L420,400 L410,460 L350,480 L310,440 Z', // นครราชสีมา
    '31': 'M350,480 L410,460 L440,500 L420,550 L370,540 L340,510 Z', // บุรีรัมย์
    '32': 'M370,540 L420,550 L450,590 L420,630 L380,610 L360,570 Z', // สุรินทร์
    '33': 'M380,610 L420,630 L460,620 L470,670 L430,690 L390,660 Z', // ศรีสะเกษ
    '34': 'M430,690 L470,670 L510,700 L500,750 L450,740 L420,710 Z', // อุบลราชธานี
    '35': 'M390,660 L430,690 L420,730 L380,720 L370,680 Z', // ยโสธร
    '36': 'M290,340 L350,330 L380,380 L350,420 L300,400 L280,360 Z', // ชัยภูมิ
    '37': 'M420,710 L460,700 L480,730 L450,760 L420,750 Z', // อำนาจเจริญ
    '38': 'M380,180 L420,160 L450,190 L430,230 L390,220 Z', // บึงกาฬ
    '39': 'M340,280 L380,260 L400,300 L380,340 L340,330 Z', // หนองบัวลำภู
    '40': 'M350,330 L400,320 L430,370 L410,420 L360,400 L340,360 Z', // ขอนแก่น
    '41': 'M340,230 L390,220 L420,270 L400,320 L350,310 L330,270 Z', // อุดรธานี
    '42': 'M290,230 L340,230 L350,280 L320,320 L280,300 L270,250 Z', // เลย
    '43': 'M360,180 L400,170 L420,200 L400,240 L360,230 Z', // หนองคาย
    '44': 'M380,380 L420,370 L450,420 L430,460 L390,450 L370,410 Z', // มหาสารคาม
    '45': 'M410,420 L460,410 L490,460 L470,510 L430,490 L400,460 Z', // ร้อยเอ็ด
    '46': 'M400,320 L450,310 L480,360 L460,400 L420,390 L390,350 Z', // กาฬสินธุ์
    '47': 'M400,240 L450,230 L480,280 L460,330 L420,310 L390,280 Z', // สกลนคร
    '48': 'M450,190 L490,180 L520,220 L500,270 L460,250 L440,220 Z', // นครพนม
    '49': 'M480,280 L520,270 L540,320 L520,370 L480,350 Z', // มุกดาหาร

    // ภาคกลาง และ กทม.
    '10': 'M230,470 L270,460 L290,500 L270,540 L230,530 L210,490 Z', // กรุงเทพมหานคร
    '11': 'M270,540 L310,530 L330,570 L310,610 L270,600 L250,560 Z', // สมุทรปราการ
    '12': 'M220,440 L260,430 L280,470 L260,510 L220,500 Z', // นนทบุรี
    '13': 'M240,400 L280,390 L300,430 L280,470 L240,460 Z', // ปทุมธานี
    '14': 'M240,360 L290,350 L310,390 L290,430 L250,420 L230,380 Z', // พระนครศรีอยุธยา
    '15': 'M210,370 L240,360 L250,400 L230,430 L200,410 Z', // อ่างทอง
    '16': 'M260,310 L300,300 L320,340 L300,380 L260,370 Z', // ลพบุรี
    '17': 'M220,340 L250,330 L260,360 L240,390 L210,380 Z', // สิงห์บุรี
    '18': 'M190,340 L220,330 L230,370 L210,400 L180,380 Z', // ชัยนาท
    '19': 'M280,370 L320,360 L340,400 L320,440 L280,430 Z', // สระบุรี
    '60': 'M230,270 L280,260 L300,300 L280,340 L230,330 L210,290 Z', // นครสวรรค์
    '61': 'M180,290 L220,280 L230,330 L210,370 L170,350 Z', // อุทัยธานี
    '62': 'M210,300 L250,290 L270,330 L250,370 L210,360 L190,320 Z', // กำแพงเพชร
    '63': 'M120,280 L170,270 L190,320 L170,370 L130,360 L100,310 Z', // ตาก
    '64': 'M270,330 L310,320 L330,360 L310,400 L270,390 L250,350 Z', // สุโขทัย
    '65': 'M290,300 L340,290 L360,340 L340,380 L300,370 L280,330 Z', // พิษณุโลก
    '66': 'M250,340 L290,330 L310,370 L290,410 L250,400 Z', // พิจิตร
    '67': 'M310,310 L360,300 L380,350 L360,400 L320,380 L300,340 Z', // เพชรบูรณ์
    '72': 'M170,420 L210,410 L230,450 L210,490 L170,480 Z', // สุพรรณบุรี
    '73': 'M190,470 L230,460 L250,500 L230,540 L190,530 Z', // นครปฐม
    '74': 'M170,520 L210,510 L230,550 L210,590 L170,580 Z', // สมุทรสาคร
    '75': 'M160,560 L190,550 L210,590 L190,630 L160,620 Z', // สมุทรสงคราม

    // ภาคตะวันออก
    '20': 'M310,500 L360,490 L390,540 L370,590 L320,580 L300,540 Z', // ชลบุรี
    '21': 'M360,550 L400,540 L430,590 L410,640 L370,620 L350,580 Z', // ระยอง
    '22': 'M400,590 L450,580 L480,630 L460,680 L410,660 L390,620 Z', // จันทบุรี
    '23': 'M460,630 L500,620 L530,670 L510,720 L470,700 L450,660 Z', // ตราด
    '24': 'M300,440 L350,430 L380,480 L360,530 L310,510 L290,470 Z', // ฉะเชิงเทรา
    '25': 'M330,390 L380,380 L410,430 L390,480 L340,460 L320,420 Z', // ปราจีนบุรี
    '26': 'M290,410 L330,400 L350,440 L330,480 L290,470 Z', // นครนายก
    '27': 'M380,430 L430,420 L460,470 L440,520 L390,500 L370,460 Z', // สระแก้ว

    // ภาคตะวันตก
    '70': 'M140,470 L180,460 L200,510 L180,560 L140,540 Z', // ราชบุรี
    '71': 'M100,420 L150,400 L180,460 L150,520 L100,500 L80,450 Z', // กาญจนบุรี
    '76': 'M130,560 L170,550 L190,600 L170,650 L130,640 Z', // เพชรบุรี
    '77': 'M120,640 L160,630 L180,680 L160,730 L120,720 Z', // ประจวบคีรีขันธ์

    // ภาคใต้
    '80': 'M200,820 L260,800 L300,860 L280,920 L220,940 L180,880 Z', // นครศรีธรรมราช
    '81': 'M140,880 L180,870 L200,920 L180,970 L140,960 Z', // กระบี่
    '82': 'M110,840 L150,830 L170,880 L150,930 L110,920 Z', // พังงา
    '83': 'M100,920 L140,910 L160,960 L140,1010 L100,1000 Z', // ภูเก็ต
    '84': 'M160,760 L220,740 L260,800 L230,860 L170,870 L140,810 Z', // สุราษฎร์ธานี
    '85': 'M100,790 L140,780 L160,830 L140,880 L100,870 Z', // ระนอง
    '86': 'M130,730 L180,720 L200,770 L180,820 L130,810 Z', // ชุมพร
    '90': 'M220,960 L280,940 L320,1000 L300,1060 L240,1080 L200,1020 Z', // สงขลา
    '91': 'M160,1020 L200,1010 L220,1060 L200,1110 L160,1100 Z', // สตูล
    '92': 'M170,950 L210,940 L230,990 L210,1040 L170,1030 Z', // ตรัง
    '93': 'M200,900 L240,890 L260,940 L240,990 L200,980 Z', // พัทลุง
    '94': 'M260,1040 L300,1030 L330,1080 L310,1130 L270,1120 L250,1070 Z', // ปัตตานี
    '95': 'M300,1080 L340,1070 L370,1120 L350,1170 L310,1160 L290,1110 Z', // ยะลา
    '96': 'M340,1110 L380,1100 L410,1150 L390,1200 L350,1190 L330,1140 Z', // นราธิวาส
};

// ฟังก์ชันสำหรับหาจังหวัดจาก code
export const getProvinceByCode = (code) => provinces.find(p => p.code === code);

// ฟังก์ชันสำหรับหาจังหวัดตาม region
export const getProvincesByRegion = (region) => provinces.filter(p => p.region === region);

// Export default
export default {
    regions,
    provinces,
    provincePaths,
    totalConstituencies,
    getProvinceByCode,
    getProvincesByRegion,
};
