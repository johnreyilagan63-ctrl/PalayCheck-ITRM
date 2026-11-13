<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Advice;
use App\Models\Detection;
use App\Models\Pest;
use App\Support\AdminResources;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ResourceController extends Controller
{
    public function index(string $resource)
    {
        $config = $this->config($resource);
        $search = trim((string) request('q', ''));

        $model = $config['model'];
        $query = $model::query();

        if ($resource === 'advice') {
            $query->with('pest');
        }

        if ($search !== '' && $config['search']) {
            $query->where(function ($query) use ($config, $search) {
                foreach ($config['search'] as $column) {
                    $query->orWhere($column, 'like', '%'.$search.'%');
                }
            });
        }

        return view('admin.resources.index', [
            'resource' => $resource,
            'config' => $config,
            'rows' => $query->orderByDesc('id')->get(),
            'search' => $search,
        ]);
    }

    public function create(string $resource)
    {
        $config = $this->config($resource);
        $model = $config['model'];
        $record = new $model(AdminResources::defaults($resource));

        return view('admin.resources.form', $this->formData($resource, $config, $record, true));
    }

    public function store(Request $request, string $resource)
    {
        return $this->save($request, $resource, null, true);
    }

    public function edit(string $resource, int $id)
    {
        $config = $this->config($resource);
        $model = $config['model'];
        $record = $model::findOrFail($id);

        return view('admin.resources.form', $this->formData($resource, $config, $record, false));
    }

    public function update(Request $request, string $resource, int $id)
    {
        return $this->save($request, $resource, $id, false);
    }

    public function destroy(string $resource, int $id)
    {
        $config = $this->config($resource);
        $model = $config['model'];
        $record = $model::findOrFail($id);

        if ($resource === 'users' && (int) $record->id === (int) auth()->id()) {
            flash('You cannot delete your own account.', 'danger');

            return redirect()->route('admin.resources.index', $resource);
        }

        DB::transaction(function () use ($resource, $record): void {
            if ($resource === 'users') {
                foreach (Detection::where('user_id', $record->id)->get() as $detection) {
                    remove_detection($detection);
                }

                ActivityLog::where('user_id', $record->id)->update(['user_id' => null]);
            }

            if ($resource === 'pests') {
                Advice::where('pest_id', $record->id)->delete();
                Detection::where('pest_id', $record->id)->update(['pest_id' => null]);
            }

            $record->delete();
        });

        log_activity('admin_delete', $resource.' #'.$id);
        flash('Deleted.');

        return redirect()->route('admin.resources.index', $resource);
    }

    private function save(Request $request, string $resource, ?int $id, bool $isNew)
    {
        $config = $this->config($resource);
        $model = $config['model'];
        $record = $isNew
            ? new $model(AdminResources::defaults($resource))
            : $model::findOrFail($id);

        $values = [];

        foreach ($config['fields'] as $name => $field) {
            if ($name === 'password') {
                $password = (string) $request->input('password', '');

                if ($password !== '') {
                    if (strlen($password) < 6) {
                        flash('Password must be at least 6 characters.', 'danger');

                        return back()->withInput();
                    }

                    if (strlen($password) > 72) {
                        flash('Password must be no more than 72 bytes.', 'danger');

                        return back()->withInput();
                    }

                    $record->password_hash = Hash::make($password);
                } elseif ($isNew) {
                    flash('Password is required for new users.', 'danger');

                    return back()->withInput();
                }

                continue;
            }

            $raw = $request->input($name, '');
            $value = is_string($raw) ? trim($raw) : $raw;

            if (($field['required'] ?? false) && ($value === '' || $value === null)) {
                flash($field['label'].' is required.', 'danger');

                return back()->withInput();
            }

            if ($field['type'] === 'select' && ! in_array($value, $field['options'], true)) {
                flash('Invalid '.strtolower($field['label']).'.', 'danger');

                return back()->withInput();
            }

            if ($field['type'] === 'pest') {
                if (! Pest::whereKey((int) $value)->exists()) {
                    flash('Choose a valid pest.', 'danger');

                    return back()->withInput();
                }

                $value = (int) $value;
            }

            $values[$name] = $value;
        }

        if ($isNew && $resource === 'users') {
            $values['created_at'] = now();
        }

        $record->fill($values);

        try {
            $record->save();
        } catch (QueryException $error) {
            if ((string) $error->getCode() !== '23000') {
                throw $error;
            }

            flash('Duplicate value (username, email or name already exists).', 'danger');

            return back()->withInput();
        }

        log_activity('admin_save', $resource.': '.(string) reset($values));
        flash('Saved.');

        return redirect()->route('admin.resources.index', $resource);
    }

    private function formData(string $resource, array $config, $record, bool $isNew): array
    {
        return [
            'resource' => $resource,
            'config' => $config,
            'record' => $record,
            'isNew' => $isNew,
            'pests' => $resource === 'advice' ? Pest::orderBy('name')->get() : collect(),
        ];
    }

    private function config(string $resource): array
    {
        $config = AdminResources::get($resource);
        abort_if($config === null, 404);

        return $config;
    }
}
