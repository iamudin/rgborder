<?php

namespace Database\Seeders;

use App\Models\ContentPage;
use Illuminate\Database\Seeder;

class ContentPageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'title' => 'Tentang RGB Arena',
                'slug' => 'about',
                'category' => 'general',
                'excerpt' => 'Mengenal RGB Arena, pelopor wahana pertarungan laser combat taktis berkecepatan tinggi dengan teknologi scoring nirkabel real-time di Indonesia.',
                'content' => '
                    <h2>Selamat Datang di RGB Arena</h2>
                    <p>RGB Arena adalah arena pertempuran laser combat taktis generasi masa depan yang dirancang untuk memberikan pengalaman bermain game first-person shooter (FPS) secara nyata di dunia nyata. Berdiri di jantung kota dengan konsep labirin neon futuristik bertema cyberpunk, RGB Arena memadukan adrenalin, kecepatan gerak, dan strategi tim tanpa risiko sakit atau memar.</p>

                    <h3>Misi Kami</h3>
                    <p>Kami hadir untuk mentransformasi hiburan kelompok dan komunitas olahraga elektronik menjadi aktivitas fisik yang menyehatkan, kompetitif, dan penuh kebersamaan. Melalui sistem otomatisasi pembagian tim dan antrean digital, setiap pemain—baik datang sendiri maupun beramai-ramai—dijamin mendapatkan slot bermain yang adil dan seimbang.</p>

                    <h3>Mengapa Memilih RGB Arena?</h3>
                    <ul>
                        <li><strong>Teknologi Sensor Presisi Tinggi:</strong> Rompi dan blaster laser inframerah berakurasi milimeter dengan feedback getaran haptik dan efek suara surround.</li>
                        <li><strong>Labirin Modular Berlampu Neon:</strong> Lorong dan rintangan dirancang khusus oleh arsitek taktis untuk memaksimalkan strategi flank, cover, dan ambush.</li>
                        <li><strong>Sistem Pembagian Tim Adil:</strong> Algoritma pintar kami memastikan jumlah dan komposisi pemain selalu berimbang antara Tim Merah dan Tim Biru.</li>
                        <li><strong>Aman dan Bersih:</strong> Permainan 100% menggunakan pancaran cahaya inframerah ramah mata tanpa proyektil fisik, cat, atau residu kotoran.</li>
                    </ul>

                    <h3>Lokasi & Jam Operasional</h3>
                    <p>RGB Arena beroperasi setiap hari mulai pukul 10:00 hingga 22:00 WIB. Arena kami ramah untuk berbagai kelompok umur, mulai dari remaja, mahasiswa, komunitas gamer, hingga outing gathering perusahaan.</p>
                ',
                'featured_image' => 'images/hero/slide-2-blue.jpg',
                'is_published' => true,
                'meta_title' => 'Tentang RGB Arena — Laser Combat Taktis Futuristik',
                'meta_description' => 'Pelajari profil, visi, dan keunggulan RGB Arena, arena laser tag futuristik terbesar dengan atmosfer cyberpunk di Indonesia.',
                'sort_order' => 1,
            ],
            [
                'title' => 'Aturan & Protokol Keselamatan',
                'slug' => 'rules',
                'category' => 'rules',
                'excerpt' => 'Panduan keselamatan wajib, briefing perlengkapan tempur, dan aturan sportivitas selama berada di dalam labirin RGB Arena.',
                'content' => '
                    <h2>Protokol Keselamatan & Kode Etik Arena</h2>
                    <p>Keselamatan dan kenyamanan seluruh pemain adalah prioritas mutlak kami. Sebelum memasuki zona pertempuran RGB Arena, seluruh peserta diwajibkan memahami dan mematuhi panduan di bawah ini.</p>

                    <h3>1. Wajib Hadir Tepat Waktu</h3>
                    <p>Pemain wajib tiba di meja resepsionis RGB Arena minimal <strong>15 menit sebelum waktu sesi dimulai</strong> untuk proses registrasi QR Check-in, penitipan barang di loker, dan sesi pengarahan (safety briefing).</p>

                    <h3>2. Aturan Perlengkapan & Pakaian</h3>
                    <ul>
                        <li>Pemain <strong>wajib mengenakan sepatu beralas datar</strong> (sneakers/sepatu olahraga). Dilarang keras menggunakan sandal jepit, sepatu hak tinggi, atau bertelanjang kaki.</li>
                        <li>Disarankan memakai pakaian yang nyaman menyerap keringat.</li>
                        <li>Lepaskan aksesoris logam tajam atau perhiasan yang berpotensi tersangkut rintangan labirin.</li>
                    </ul>

                    <h3>3. Perilaku Selama di Dalam Labirin Tempur</h3>
                    <ul>
                        <li><strong>DILARANG KONTAK FISIK:</strong> Tidak boleh menyenggol, mendorong, menahan, atau memegang tubuh lawan maupun kawan.</li>
                        <li><strong>DILARANG MEMANJAT:</strong> Rintangan labirin dibuat untuk berlindung, bukan untuk dinaiki atau digeser.</li>
                        <li><strong>DUA TANGAN PADA BLASTER:</strong> Senjata laser harus dipegang dengan kedua tangan sesuai sensor keselamatan agar tembakan aktif.</li>
                        <li><strong>SPORTIVITAS & FAIR PLAY:</strong> Jangan menutupi sensor di rompi Anda dengan tangan atau pakaian untuk menghindari tembakan. Sistem akan mencatat penalti otomatis jika terdeteksi kecurangan.</li>
                    </ul>

                    <h3>4. Batasan Usia & Kesehatan</h3>
                    <p>Permainan ini dapat diikuti oleh peserta usia minimal 8 tahun. Peserta dengan riwayat gangguan jantung kronis atau epilepsi fotosensitif (terhadap kilatan lampu strobo) disarankan berkonsultasi terlebih dahulu dengan petugas arena.</p>
                ',
                'featured_image' => 'images/hero/slide-1-red.jpg',
                'is_published' => true,
                'meta_title' => 'Aturan & Keselamatan — RGB Arena',
                'meta_description' => 'Panduan lengkap aturan main, briefing keselamatan, dan pakaian yang direkomendasikan untuk bermain di RGB Arena.',
                'sort_order' => 2,
            ],
            [
                'title' => 'Fasilitas & Perlengkapan Tempur',
                'slug' => 'facilities',
                'category' => 'facilities',
                'excerpt' => 'Spesifikasi senjata laser RGB, rompi sensorik haptik, lounge ber-AC, dan fasilitas pendukung pertempuran lainnya.',
                'content' => '
                    <h2>Fasilitas Tempur Standar Kompetisi</h2>
                    <p>RGB Arena dilengkapi dengan infrastruktur mutakhir untuk menunjang pengalaman bermain yang imersif dan memuaskan bagi seluruh pasukan pertempuran.</p>

                    <h3>1. Senjata Laser Cyber-Blaster Mark IV</h3>
                    <p>Blaster tempur berbobot seimbang dengan display OLED mini yang menampilkan sisa amunisi, kesehatan (HP), dan status tim Anda. Dilengkapi pancaran beam neon yang terlihat di udara berkabut arena.</p>

                    <h3>2. Rompi Sensor Taktis Haptik</h3>
                    <p>Rompi taktis tahan benturan yang menyala sesuai warna tim (Merah atau Biru). Memiliki 4 zona sensor (dada, punggung, dan kedua bahu) dengan getaran getar instan saat tertembak.</p>

                    <h3>3. Labirin Tempur Berpendingin Ruangan (Full AC)</h3>
                    <p>Arena bermain tertutup seluas lebih dari 400 meter persegi dengan tata cahaya RGB dinamis, kabut atmosferik khusus (haze), dan soundtrack elektronik yang memacu detak jantung.</p>

                    <h3>4. Fasilitas Pendukung Lainnya</h3>
                    <ul>
                        <li><strong>Player Lounge & Cafe:</strong> Area istirahat nyaman ber-AC untuk relaksasi sambil menikmati minuman dingin dan snack.</li>
                        <li><strong>Loker Elektronik Gratis:</strong> Amankan barang bawaan, ponsel, dan dompet Anda secara aman sebelum pertempuran dimulai.</li>
                        <li><strong>Monitor Skor Raksasa:</strong> Papan skor LED digital di luar arena untuk melihat statistik kill, accuracy, dan MVP tim secara langsung.</li>
                        <li><strong>Musholla & Kamar Ganti:</strong> Tersedia fasilitas ibadah dan ruang ganti bersih terpisah pria dan wanita.</li>
                    </ul>
                ',
                'featured_image' => 'images/hero/slide-2-blue.jpg',
                'is_published' => true,
                'meta_title' => 'Fasilitas & Perlengkapan Tempur — RGB Arena',
                'meta_description' => 'Lihat fasilitas lengkap RGB Arena: Cyber Blaster Mark IV, rompi sensorik, labirin neon AC, lounge, dan loker gratis.',
                'sort_order' => 3,
            ],
        ];

        foreach ($pages as $page) {
            ContentPage::updateOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }
    }
}
