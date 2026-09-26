<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\Archive;
use App\Services\Edition;

/** Past editions (archive, browse, export, rename, delete) and the current edition's details. */
final class ArchiveController extends Controller
{
    /** What an admin must type before live data is emptied or an archive is dropped. */
    private const CONFIRM_WORD = 'ARCHIVE';

    public function index(Request $request): Response
    {
        return $this->view('admin/archives', [
            'title'    => 'Archive',
            'flash'    => (string) Session::get('admin_flash', ''),
            'archives' => Archive::all(),
            'tables'   => Archive::TABLES,
            'edition'  => (string) config('app.summit.edition'),
            'confirm'  => self::CONFIRM_WORD,
        ], 'layouts/admin');
    }

    public function create(Request $request): Response
    {
        $clear = $request->input('clear') === '1';
        if ($clear && trim($request->str('confirm')) !== self::CONFIRM_WORD) {
            Session::flash('admin_flash', 'Type ' . self::CONFIRM_WORD . ' to archive and start a new edition. Nothing was changed.');
            return $this->redirect('/admin/archives');
        }

        $error = Archive::create($request->str('label'), $clear, (string) Session::get('admin_email', ''));
        Session::flash('admin_flash', $error ?? ($clear
            ? 'Archived. The live tables are empty and registration starts fresh for the next edition.'
            : 'Archived. The live data is untouched.'));

        return $this->redirect('/admin/archives');
    }

    public function show(Request $request): Response
    {
        $archive = Archive::find($request->str('slug'));
        if ($archive === null) {
            return $this->redirect('/admin/archives');
        }
        $table = $request->str('table', 'registrations');
        $table = isset(Archive::TABLES[$table]) ? $table : 'registrations';
        $search = trim($request->str('q'));

        return $this->view('admin/archive', [
            'title'   => $archive['label'],
            'flash'   => (string) Session::get('admin_flash', ''),
            'archive' => $archive,
            'tables'  => Archive::TABLES,
            'table'   => $table,
            'search'  => $search,
            'result'  => Archive::rows($archive['slug'], $table, $search, max(1, (int) $request->input('page', 1))),
            'confirm' => self::CONFIRM_WORD,
        ], 'layouts/admin');
    }

    public function csv(Request $request): Response
    {
        $archive = Archive::find($request->str('slug'));
        $table = $request->str('table', 'registrations');
        if ($archive === null || !isset(Archive::TABLES[$table])) {
            return $this->redirect('/admin/archives');
        }

        $handle = fopen('php://temp', 'r+');
        foreach (Archive::each($archive['slug'], $table, trim($request->str('q'))) as $row) {
            fputcsv($handle, array_map(static fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v, $row));
        }
        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return Response::download("\xEF\xBB\xBF" . $csv, 'producers-summit-' . $archive['slug'] . '-' . str_replace('_', '-', $table) . '.csv');
    }

    public function rename(Request $request): Response
    {
        $slug = $request->str('slug');
        Session::flash('admin_flash', Archive::rename($slug, $request->str('label')) ? 'Renamed.' : 'That name could not be used.');

        return $this->redirect('/admin/archives/view?slug=' . rawurlencode($slug));
    }

    public function delete(Request $request): Response
    {
        $slug = $request->str('slug');
        if (trim($request->str('confirm')) !== $slug) {
            Session::flash('admin_flash', 'Type the archive\'s short name (' . $slug . ') to delete it. Nothing was deleted.');
            return $this->redirect('/admin/archives/view?slug=' . rawurlencode($slug));
        }
        Session::flash('admin_flash', Archive::delete($slug) ? 'Archive deleted for good.' : 'That archive was already gone.');

        return $this->redirect('/admin/archives');
    }

    public function edition(Request $request): Response
    {
        return $this->view('admin/edition', [
            'title'  => 'Edition',
            'flash'  => (string) Session::get('admin_flash', ''),
            'fields' => Edition::FIELDS,
            'values' => array_replace(self::flatten((array) config('app.summit')), Edition::saved()),
        ], 'layouts/admin');
    }

    public function saveEdition(Request $request): Response
    {
        $values = [];
        foreach (array_keys(Edition::FIELDS) as $key) {
            $values[$key] = $request->str(str_replace('.', '__', $key));
        }
        if (trim($values['edition']) === '' || trim($values['place']) === '') {
            Session::flash('admin_flash', 'The edition needs a name and a place. Nothing was saved.');
            return $this->redirect('/admin/edition');
        }
        Edition::save($values);
        Session::flash('admin_flash', 'Saved. The site, emails and calendar files use these details now.');

        return $this->redirect('/admin/edition');
    }

    /** ['venue' => ['name' => …]] to ['venue.name' => …], limited to the form's fields. */
    private static function flatten(array $summit): array
    {
        $flat = [];
        foreach (array_keys(Edition::FIELDS) as $key) {
            [$a, $b] = array_pad(explode('.', $key, 2), 2, null);
            $flat[$key] = (string) ($b === null ? ($summit[$a] ?? '') : ($summit[$a][$b] ?? ''));
        }

        return $flat;
    }
}
