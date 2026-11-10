<?php
/**
 * Template HTML surat (diproses dompdf). Berkas PHP biasa, bukan Blade, agar ringan dan mudah diuji.
 * Semua nilai sudah disiapkan oleh LetterRenderer::context(); $e() = escape HTML.
 *
 * @var callable $e
 * @var array    $c   konteks surat
 */
$fs = (int) $c['font_size'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 1.6cm 2.2cm 2cm 2.5cm; }
    body { font-family: "Times New Roman", Times, serif; font-size: <?= $fs ?>pt; line-height: 1.35; color: #000; margin: 0; }
    table { border-collapse: collapse; }
    td { vertical-align: top; padding: 0; }
    .kop { width: 100%; }
    .kop-line { border-top: 3px double #000; margin: 6px 0 14px; height: 0; }
    .kop td.logo { width: 80px; }
    .kop td.txt { text-align: center; }
    .kop .l1 { font-size: <?= $fs + 3 ?>pt; font-weight: bold; line-height: 1.2; }
    .kop .addr { font-size: <?= $fs - 2 ?>pt; margin-top: 2px; }
    .meta { width: 100%; }
    .meta td.k { width: 75px; }
    .meta td.s { width: 12px; }
    .date { text-align: right; }
    .title { text-align: center; font-weight: bold; text-decoration: underline; font-size: <?= $fs + 1 ?>pt; margin: 6px 0 0; }
    .titlenum { text-align: center; margin: 0 0 14px; }
    .to { margin: 14px 0 12px; }
    p { margin: 0 0 9px; text-align: justify; }
    .kv { margin: 0 0 9px 28px; }
    .kv td.k { width: 120px; padding-right: 4px; }
    .kv td.s { width: 14px; }
    .sign { width: 100%; margin-top: 18px; page-break-inside: avoid; }
    .sign td.right { width: 52%; text-align: left; }
    .sig-space { height: 78px; }
    .sig-img { height: 70px; }
    .draft-mark { color: #b45309; }
</style>
</head>
<body>

<table class="kop"><tr>
    <?php if ($c['logo']): ?><td class="logo"><img src="<?= $c['logo'] ?>" style="height:70px"></td><?php endif; ?>
    <td class="txt">
        <?php foreach ($c['kop'] as $i => $line): ?>
            <div class="l1"><?= $e($line) ?></div>
        <?php endforeach; ?>
        <?php if ($c['kop_address']): ?><div class="addr"><?= $e($c['kop_address']) ?></div><?php endif; ?>
    </td>
    <?php if ($c['logo']): ?><td class="logo"></td><?php endif; ?>
</tr></table>
<div class="kop-line"></div>

<?php if ($c['title']): ?>
    <div class="date"><?= $e($c['place']) ?>, <?= $e($c['date_text']) ?></div>
    <div class="title"><?= $e($c['title']) ?></div>
    <div class="titlenum">Nomor: <?= $c['number'] !== '' ? $e($c['number']) : '<span class="draft-mark">[diterbitkan saat ACC]</span>' ?></div>
<?php else: ?>
    <table class="meta">
        <tr>
            <td class="k">Nomor</td><td class="s">:</td>
            <td><?= $c['number'] !== '' ? $e($c['number']) : '<span class="draft-mark">[diterbitkan saat ACC]</span>' ?></td>
            <td class="date" rowspan="3" style="width:34%"><?= $e($c['place']) ?>, <?= $e($c['date_text']) ?></td>
        </tr>
        <tr><td class="k">Lampiran</td><td class="s">:</td><td><?= $e($c['attachment'] !== '' ? $c['attachment'] : '-') ?></td></tr>
        <tr><td class="k">Perihal</td><td class="s">:</td><td><b><?= $e($c['subject']) ?></b></td></tr>
    </table>
    <?php if ($c['recipient_lines']): ?>
        <div class="to">
            Yth.<br>
            <?php foreach ($c['recipient_lines'] as $line): ?><?= $e($line) ?><br><?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php foreach ($c['blocks'] as $b): ?>
    <?php if ($b['type'] === 'kv'): ?>
        <table class="kv">
            <?php foreach ($b['rows'] as [$k, $v]): ?>
                <tr><td class="k"><?= $e($k) ?></td><td class="s">:</td><td><?= $e($v) ?></td></tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p><?= implode('<br>', array_map($e, $b['lines'])) ?></p>
    <?php endif; ?>
<?php endforeach; ?>

<table class="sign"><tr>
    <td></td>
    <td class="right">
        <?= $e($c['signer_title']) ?><br>
        <?php if ($c['signature']): ?>
            <img class="sig-img" src="<?= $c['signature'] ?>"><br>
        <?php else: ?>
            <div class="sig-space"></div>
        <?php endif; ?>
        <b><u><?= $e($c['signer_name']) ?></u></b><br>
        <?php if ($c['signer_nip'] !== ''): ?>NIP. <?= $e($c['signer_nip']) ?><?php endif; ?>
    </td>
</tr></table>

</body>
</html>
