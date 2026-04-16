<?php
include 'config/koneksi.php';

// Atur Timezone ke WIB sesuai lokasi user
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];
$search = isset($_GET['q']) ? mysqli_real_escape_string($koneksi, trim($_GET['q'])) : '';
$folder_id = isset($_GET['folder']) ? (int) $_GET['folder'] : null;

$is_search_mode = !empty($search);
$user_filter = "";

if ($is_search_mode) {
    $q = "SELECT d.*, fol.nama_folder as nama_folder_parent
          FROM documents d
          LEFT JOIN folders fol ON d.folder_id = fol.id
          WHERE (d.judul_dokumen LIKE '%$search%'
             OR d.id IN (SELECT document_id FROM attachments WHERE nama_asli LIKE '%$search%' OR keterangan LIKE '%$search%'))
             $user_filter
          ORDER BY d.tanggal_upload DESC";
    $title = "Laporan Pencarian: " . $search;
} else {
    if ($folder_id) {
        $q = "SELECT d.*, fol.nama_folder as nama_folder_parent
              FROM documents d
              LEFT JOIN folders fol ON d.folder_id = fol.id
              WHERE d.folder_id = $folder_id $user_filter ORDER BY d.tanggal_upload DESC";
        $fq = mysqli_query($koneksi, "SELECT nama_folder FROM folders WHERE id = $folder_id");
        $fol = mysqli_fetch_assoc($fq);
        $title = "Laporan Kategori: " . ($fol ? $fol['nama_folder'] : 'Kategori');
    } else {
        $q = "SELECT d.*, NULL as nama_folder_parent FROM documents d WHERE d.folder_id IS NULL $user_filter ORDER BY d.tanggal_upload DESC";
        $title = "Laporan Dokumen Tanpa Kategori";
    }
}
$files_data = mysqli_query($koneksi, $q);

// ============================================================
// DOCX GENERATION (OpenXML / Word Mobile compatible)
// ============================================================

if (!class_exists('ZipArchive')) {
    die("Extension php_zip belum aktif. Silakan aktifkan extension=zip di php.ini dan restart Apache.");
}

$zip = new ZipArchive();
$temp_file = tempnam(sys_get_temp_dir(), 'docx');
if ($zip->open($temp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die("Gagal membuat file .docx");
}

// --- Collect images and relationship IDs ---
$image_rels = []; // array of ['rid' => ..., 'target' => ..., 'ext' => ...]
$rel_counter = 1;
$img_counter = 1;

// --- Build table rows XML ---
$rows_xml = '';

if (mysqli_num_rows($files_data) > 0) {
    $no = 1;
    while ($d = mysqli_fetch_assoc($files_data)) {
        $doc_id = $d['id'];
        $q_atts = mysqli_query($koneksi, "SELECT nama_asli, nama_file, keterangan FROM attachments WHERE document_id = $doc_id");

        $att_xml = '';
        while ($a = mysqli_fetch_assoc($q_atts)) {
            $ext = strtolower(pathinfo($a['nama_file'], PATHINFO_EXTENSION));
            $is_img = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif']);

            // Nama file
            $att_xml .= '<w:p w:rsidR="00000000" w:rsidRDefault="00000000"><w:r><w:rPr><w:b/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t xml:space="preserve">- ' . htmlspecialchars($a['nama_asli'], ENT_XML1, 'UTF-8') . '</w:t></w:r></w:p>';

            // Keterangan
            if (!empty($a['keterangan'])) {
                $att_xml .= '<w:p w:rsidR="00000000" w:rsidRDefault="00000000"><w:r><w:rPr><w:i/><w:sz w:val="18"/><w:szCs w:val="18"/></w:rPr><w:t xml:space="preserve">  ' . htmlspecialchars($a['keterangan'], ENT_XML1, 'UTF-8') . '</w:t></w:r></w:p>';
            }

            // Gambar
            if ($is_img) {
                $f_path = __DIR__ . '/uploads/' . $a['nama_file'];
                if (file_exists($f_path)) {
                    $rid = 'rId' . (++$rel_counter);
                    $img_name = 'image' . ($img_counter++) . '.' . ($ext === 'jfif' ? 'jpeg' : $ext);
                    $zip->addFile($f_path, 'word/media/' . $img_name);
                    $image_rels[] = ['rid' => $rid, 'target' => 'media/' . $img_name];

                    $size = @getimagesize($f_path);
                    $pw = 100;
                    $ph = 100;
                    if ($size && $size[0] > 0) {
                        $pw = 100;
                        $ph = intval(($size[1] / $size[0]) * $pw);
                    }
                    $cx = $pw * 9525;
                    $cy = $ph * 9525;

                    $att_xml .= '<w:p w:rsidR="00000000" w:rsidRDefault="00000000"><w:r><w:rPr><w:noProof/></w:rPr><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0"><wp:extent cx="' . $cx . '" cy="' . $cy . '"/><wp:effectExtent l="0" t="0" r="0" b="0"/><wp:docPr id="' . $img_counter . '" name="Picture ' . $img_counter . '"/><wp:cNvGraphicFramePr><a:graphicFrameLocks xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" noChangeAspect="1"/></wp:cNvGraphicFramePr><a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:nvPicPr><pic:cNvPr id="' . $img_counter . '" name="Picture ' . $img_counter . '"/><pic:cNvPicPr/></pic:nvPicPr><pic:blipFill><a:blip r:embed="' . $rid . '"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>';
                }
            }
        }
        if (empty($att_xml)) {
            $att_xml = '<w:p w:rsidR="00000000" w:rsidRDefault="00000000"><w:r><w:rPr><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>Tidak ada file</w:t></w:r></w:p>';
        }

        $kat = ($is_search_mode && $d['nama_folder_parent']) ? htmlspecialchars($d['nama_folder_parent'], ENT_XML1, 'UTF-8') : ($d['folder_id'] ? 'Dalam folder ini' : '-');
        $lok = (!empty($d['latitude']) && !empty($d['longitude'])) ? " (Lat: {$d['latitude']}, Lng: {$d['longitude']})" : '';
        $tgl = (!empty($d['tanggal_upload']) ? date('d M Y H:i', strtotime($d['tanggal_upload'])) : '-');
        $judul = htmlspecialchars(!empty($d['judul_dokumen']) ? $d['judul_dokumen'] : 'Tanpa Judul', ENT_XML1, 'UTF-8');

        $rows_xml .= '
        <w:tr w:rsidR="00000000">
            <w:tc><w:tcPr><w:tcW w:w="700" w:type="dxa"/><w:vAlign w:val="center"/></w:tcPr><w:p w:rsidR="00000000" w:rsidRDefault="00000000"><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>' . $no++ . '</w:t></w:r></w:p></w:tc>
            <w:tc><w:tcPr><w:tcW w:w="3000" w:type="dxa"/></w:tcPr><w:p w:rsidR="00000000" w:rsidRDefault="00000000"><w:r><w:rPr><w:b/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>' . $judul . '</w:t></w:r></w:p></w:tc>
            <w:tc><w:tcPr><w:tcW w:w="2000" w:type="dxa"/><w:vAlign w:val="center"/></w:tcPr><w:p w:rsidR="00000000" w:rsidRDefault="00000000"><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>' . $tgl . '</w:t></w:r></w:p></w:tc>
            <w:tc><w:tcPr><w:tcW w:w="2000" w:type="dxa"/></w:tcPr><w:p w:rsidR="00000000" w:rsidRDefault="00000000"><w:r><w:rPr><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>' . htmlspecialchars($kat . $lok, ENT_XML1, 'UTF-8') . '</w:t></w:r></w:p></w:tc>
            <w:tc><w:tcPr><w:tcW w:w="3300" w:type="dxa"/></w:tcPr>' . $att_xml . '</w:tc>
        </w:tr>';
    }
} else {
    $rows_xml .= '<w:tr w:rsidR="00000000"><w:tc><w:tcPr><w:tcW w:w="11000" w:type="dxa"/><w:gridSpan w:val="5"/></w:tcPr><w:p w:rsidR="00000000" w:rsidRDefault="00000000"><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:t>Tidak ada dokumen.</w:t></w:r></w:p></w:tc></w:tr>';
}

// --- Build document.xml ---
$safe_title = htmlspecialchars($title, ENT_XML1, 'UTF-8');
$cetak_waktu = date("d M Y H:i:s");

$document_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas"
            xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006"
            xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
            xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math"
            xmlns:v="urn:schemas-microsoft-com:vml"
            xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"
            xmlns:w10="urn:schemas-microsoft-com:office:word"
            xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
            xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml"
            xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
            xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"
            mc:Ignorable="w14 wp14">
<w:body>
    <w:p w:rsidR="00000000" w:rsidRDefault="00000000">
        <w:pPr><w:jc w:val="center"/></w:pPr>
        <w:r><w:rPr><w:b/><w:sz w:val="32"/><w:szCs w:val="32"/></w:rPr><w:t>' . $safe_title . '</w:t></w:r>
    </w:p>
    <w:p w:rsidR="00000000" w:rsidRDefault="00000000">
        <w:pPr><w:jc w:val="center"/></w:pPr>
        <w:r><w:rPr><w:color w:val="555555"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>Dicetak pada: ' . $cetak_waktu . '</w:t></w:r>
    </w:p>
    <w:p w:rsidR="00000000" w:rsidRDefault="00000000"/>
    <w:tbl>
        <w:tblPr>
            <w:tblStyle w:val="TableGrid"/>
            <w:tblW w:w="11000" w:type="dxa"/>
            <w:tblBorders>
                <w:top w:val="single" w:sz="4" w:space="0" w:color="000000"/>
                <w:left w:val="single" w:sz="4" w:space="0" w:color="000000"/>
                <w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000"/>
                <w:right w:val="single" w:sz="4" w:space="0" w:color="000000"/>
                <w:insideH w:val="single" w:sz="4" w:space="0" w:color="000000"/>
                <w:insideV w:val="single" w:sz="4" w:space="0" w:color="000000"/>
            </w:tblBorders>
            <w:tblLook w:val="04A0"/>
        </w:tblPr>
        <w:tblGrid>
            <w:gridCol w:w="700"/>
            <w:gridCol w:w="3000"/>
            <w:gridCol w:w="2000"/>
            <w:gridCol w:w="2000"/>
            <w:gridCol w:w="3300"/>
        </w:tblGrid>
        <w:tr w:rsidR="00000000">
            <w:tc><w:tcPr><w:tcW w:w="700" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="D9E2F3"/><w:vAlign w:val="center"/></w:tcPr><w:p w:rsidR="00000000" w:rsidRDefault="00000000"><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>No</w:t></w:r></w:p></w:tc>
            <w:tc><w:tcPr><w:tcW w:w="3000" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="D9E2F3"/><w:vAlign w:val="center"/></w:tcPr><w:p w:rsidR="00000000" w:rsidRDefault="00000000"><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>Judul Dokumen</w:t></w:r></w:p></w:tc>
            <w:tc><w:tcPr><w:tcW w:w="2000" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="D9E2F3"/><w:vAlign w:val="center"/></w:tcPr><w:p w:rsidR="00000000" w:rsidRDefault="00000000"><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>Tanggal</w:t></w:r></w:p></w:tc>
            <w:tc><w:tcPr><w:tcW w:w="2000" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="D9E2F3"/><w:vAlign w:val="center"/></w:tcPr><w:p w:rsidR="00000000" w:rsidRDefault="00000000"><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>Kategori</w:t></w:r></w:p></w:tc>
            <w:tc><w:tcPr><w:tcW w:w="3300" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="D9E2F3"/><w:vAlign w:val="center"/></w:tcPr><w:p w:rsidR="00000000" w:rsidRDefault="00000000"><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>File Terlampir</w:t></w:r></w:p></w:tc>
        </w:tr>
        ' . $rows_xml . '
    </w:tbl>
    <w:sectPr>
        <w:pgSz w:w="11906" w:h="16838"/>
        <w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="720" w:footer="720" w:gutter="0"/>
    </w:sectPr>
</w:body>
</w:document>';

// --- Build document.xml.rels ---
$doc_rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
foreach ($image_rels as $ir) {
    $doc_rels .= '
    <Relationship Id="' . $ir['rid'] . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="' . $ir['target'] . '"/>';
}
$doc_rels .= '
</Relationships>';

// --- styles.xml (minimal, required by Word Mobile) ---
$styles_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:docDefaults>
        <w:rPrDefault>
            <w:rPr>
                <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>
                <w:sz w:val="22"/>
                <w:szCs w:val="22"/>
                <w:lang w:val="id-ID"/>
            </w:rPr>
        </w:rPrDefault>
        <w:pPrDefault>
            <w:pPr>
                <w:spacing w:after="0" w:line="240" w:lineRule="auto"/>
            </w:pPr>
        </w:pPrDefault>
    </w:docDefaults>
    <w:style w:type="table" w:styleId="TableGrid">
        <w:name w:val="Table Grid"/>
        <w:basedOn w:val="TableNormal"/>
        <w:tblPr>
            <w:tblBorders>
                <w:top w:val="single" w:sz="4" w:space="0" w:color="000000"/>
                <w:left w:val="single" w:sz="4" w:space="0" w:color="000000"/>
                <w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000"/>
                <w:right w:val="single" w:sz="4" w:space="0" w:color="000000"/>
                <w:insideH w:val="single" w:sz="4" w:space="0" w:color="000000"/>
                <w:insideV w:val="single" w:sz="4" w:space="0" w:color="000000"/>
            </w:tblBorders>
        </w:tblPr>
    </w:style>
    <w:style w:type="table" w:default="1" w:styleId="TableNormal">
        <w:name w:val="Normal Table"/>
        <w:tblPr>
            <w:tblCellMar>
                <w:top w:w="0" w:type="dxa"/>
                <w:left w:w="108" w:type="dxa"/>
                <w:bottom w:w="0" w:type="dxa"/>
                <w:right w:w="108" w:type="dxa"/>
            </w:tblCellMar>
        </w:tblPr>
    </w:style>
</w:styles>';

// --- [Content_Types].xml ---
$content_types = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Default Extension="png" ContentType="image/png"/>
    <Default Extension="jpg" ContentType="image/jpeg"/>
    <Default Extension="jpeg" ContentType="image/jpeg"/>
    <Default Extension="gif" ContentType="image/gif"/>
    <Default Extension="webp" ContentType="image/webp"/>
    <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
    <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
</Types>';

// --- _rels/.rels ---
$root_rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>';

// --- Add strings to ZIP ---
$zip->addFromString('[Content_Types].xml', $content_types);
$zip->addFromString('_rels/.rels', $root_rels);
$zip->addFromString('word/document.xml', $document_xml);
$zip->addFromString('word/_rels/document.xml.rels', $doc_rels);
$zip->addFromString('word/styles.xml', $styles_xml);

$zip->close();

// --- Send to browser ---
header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
header("Content-Disposition: attachment; filename=\"Laporan_Dokumen_" . date('Ymd_His') . ".docx\"");
header("Content-Length: " . filesize($temp_file));
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
readfile($temp_file);
unlink($temp_file);
exit;
?>