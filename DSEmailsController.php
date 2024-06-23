<?php

namespace App\Http\Controllers\datacenter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Exception;

class DSEmailsController extends Controller
{
    public function index(REQUEST $request)
    {
        try {
            $id     = $request->id;
            $email  = $request->email;
            $search = $request->search;
            $status = $request->status;

            if (!isset($status) || $status === null) {
                $status = 'active';
            }

            if (isset($request->limit) && $request->limit !== null && $request->limit != '') {
                $limit = $request->limit;
            } else {
                $limit = 10;
            }

            $query = DB::table('dt_employee_email')->orderBy('id', 'DESC');

            if (isset($id) || $id !== null) {
                $data = $query->where('id', $id)->first();
            } elseif (isset($email) || $email !== null) {
                $data = $query->where('em_email', $email)->first();
            } elseif (isset($search) || $search !== null) {
                $data = $query->where('em_name', 'like', '%' . $search . '%')->orWhere('em_email', 'like', '%' . $search . '%')->limit($limit)->where('em_status', $status)->get();
            } else {
                $data = $query->limit($limit)->where('em_status', $status)->get();
            }
            $response = [
                'status'  => true,
                'message' => 'list data emails employee',
                'count'   => is_array($data) || $data instanceof \Countable ? $data->count() : 1,
                'data'    => $data
            ];
        } catch (Exception $e) {
            $response = [
                'status'  => false,
                'message' => 'Sorry, The system has a problem',
            ];
        }
        return response()->json($response);
    }
    public function create(Request $request)
    {
        try {
            $this->validate($request, [
                'em_name'  => 'required|string',
                'em_email' => 'required|email|unique:dt_employee_email,em_email',
            ]);

            DB::table('dt_employee_email')->insert([
                'em_code'        => $request->input('em_code'),
                'em_employee_id' => $request->input('em_employee_id'),
                'em_name'        => $request->input('em_name'),
                'em_email'       => $request->input('em_email'),
                'em_description' => $request->input('em_description'),
            ]);

            $data = DB::table('dt_employee_email')->latest()->first();

            return response()->json([
                'message' => 'Employee email registered successfully',
                'data'    => [
                    'em_code'        => $data->em_code,
                    'em_employee_id' => $data->em_employee_id,
                    'em_name'        => $data->em_name,
                    'em_email'       => $data->em_email,
                    'em_status'      => 'active',
                    'em_description' => $data->em_description,
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error registering employee email',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    public function update(Request $request)
    {
        try {
            $this->validate($request, [
                'id'       => 'required|int',
                'em_name'  => 'required|string',
                'em_email' => [
                    'required',
                    'email',
                    Rule::unique('dt_employee_email', 'em_email')->ignore($request->input('id')),
                ],
            ]);
            DB::table('dt_employee_email')->where('id', $request->input('id'))->update([
                'em_code'        => $request->input('em_code'),
                'em_employee_id' => $request->input('em_employee_id'),
                'em_name'        => $request->input('em_name'),
                'em_email'       => $request->input('em_email'),
                'em_description' => $request->input('em_description'),
            ]);

            $data = DB::table('dt_employee_email')->where('id', $request->input('id'))->first();

            return response()->json([
                'message' => 'Employee email updated successfully',
                'data'    => [
                    'em_code'        => $data->em_code,
                    'em_employee_id' => $data->em_employee_id,
                    'em_name'        => $data->em_name,
                    'em_email'       => $data->em_email,
                    'em_status'      => $data->em_status,
                    'em_description' => $data->em_description,
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error updating employee email',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    public function delete(REQUEST $request)
    {
        try {
            $this->validate($request, [
                'id' => 'required|int',
            ]);

            $id = $request->input('id');

            // Cek apakah id digunakan di tabel lain
            // $isReferenced = DB::table('another_table')->where('employee_email_id', $id)->exists();
            // if ($isReferenced) {
            //     return response()->json([
            //         'message' => 'Cannot delete employee email as it is referenced in another table',
            //     ], 400);
            // }

            DB::table('dt_employee_email')->where('id', $id)->delete();
            return response()->json([
                'message' => 'Employee email deleted successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error deleting employee email',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    public function soft_delete(Request $request)
    {
        try {
            $this->validate($request, [
                'id' => 'required|int',
            ]);
            $id = $request->input('id');
            // Soft delete dengan mengupdate kolom deleted_at
            DB::table('dt_employee_email')->where('id', $id)->update(['deleted_at' => now()]);

            return response()->json([
                'message' => 'Employee email deleted successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error deleting employee email',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    public function soft_delete_restore(Request $request)
    {
        try {
            $this->validate($request, [
                'id' => 'required|int',
            ]);
            $id = $request->input('id');
            // Restore dengan mengupdate kolom deleted_at menjadi null
            DB::table('dt_employee_email')->where('id', $id)->update(['deleted_at' => null]);
            return response()->json([
                'message' => 'Employee email restored successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error restoring employee email',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
