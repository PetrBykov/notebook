<?php

namespace App\Http\Controllers;

use App\Models\Notebook;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

function snakeToCamel ($str) {
    // Remove underscores, capitalize words, squash, lowercase first.
    return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $str))));
  }

class notebookController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($count, $page)
    {
        if (!is_numeric($count) || !is_numeric($page) || $count <= 0 || $page <= 0) {
            return response()->json(['message' => 'Incorrect request'], 400);
        }
        $notebook = Notebook::select('id', 'full_name', 'company', 'phone', 'email', 'date_Of_birth', 'photo_available')->skip($count * ($page - 1))->take($count)->get();
        $lastPage = ceil(Notebook::count() / $count);
        return response()->json(['records' => $notebook->toArray(), 'lastPage' => $lastPage]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'fullName' => 'required|filled',
            'company' => '',
            'phone' => 'required|filled|regex:/\+?\d{11}/',
            'email' => 'required|filled|email',
            'dateOfBirth' => '',
            'photoAvailable' => 'required|filled|boolean',
            'photoType' => [Rule::requiredIf(fn() => $request->input('photoAvailable')), Rule::in(['image/png', 'image/jpeg'])],
            'photoContent' => Rule::requiredIf(fn() => $request->input('photoAvailable')),
        ]);
        $newNotebook = new Notebook;
        $newNotebook->full_name = $request->input('fullName');
        if ($request->input('company') !== null) {
            $newNotebook->company = $request->input('company');
        }
        $newNotebook->phone = $request->input('phone');
        $newNotebook->email = $request->input('email');
        if ($request->input('dateOfBirth') !== null) {
            $newNotebook->date_of_birth = $request->input('dateOfBirth');
        }
        $newNotebook->photo_available = $request->input('photoAvailable');
        if ($request->input('photoAvailable')) {
            $newNotebook->photo_type = $request->input('photoType');
            $newNotebook->photo_content = base64_decode($request->input('photoContent'));
        }
        $newNotebook->save();
        return response()->json(['message' => 'New record added successfully', 'id' => $newNotebook->id], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $notebook = Notebook::find($id)->select('id', 'full_name', 'company', 'phone', 'email', 'date_of_birth', 'photo_available')->where('id', $id)->first();
        if ($notebook->count() === 0) {
            return response()->json(['message' => 'Record not found'], 404);
        }
        $notebook = $notebook->toArray();
        $dataToReturn = [];
        foreach($notebook as $column => $value) {
            $dataToReturn[snakeToCamel($column)] = $value;
        }
        return response()->json($dataToReturn, 200);
    }
    public function showPhoto($id)
    {
        $notebook = Notebook::select('photo_available', 'photo_type', 'photo_content')->where('id', $id)->first();
        if (!$notebook->photo_available) {
            return response()->json(['message' => 'Photo not found'], 404);
        }
        return response($notebook->photo_content, 200)->header('Content-Type', $notebook->photo_type);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'fullName' => 'filled',
            'company' => '',
            'phone' => 'filled|regex:/\+?\d{11}/',
            'email' => 'filled|email',
            'dateOfBirth' => '',
            'photoAvailable' => 'filled|boolean',
            'photoType' => [Rule::requiredIf(fn() => $request->input('photoAvailable')), Rule::in(['image/png', 'image/jpeg'])],
            'photoContent' => Rule::requiredIf(fn() => $request->input('photoAvailable')),
        ]);
        $requiredNotebook = Notebook::find($id);
        if (!$requiredNotebook) {
            return response()->json([
                'message' => 'Record not found',
            ], 404);
        }
        if ($request->input('fullName') !== null) {
            $requiredNotebook->full_name = $request->input('fullName');
        }
        
        if ($request->input('company') !== null) {
            $requiredNotebook->company = $request->input('company');
        }

        if ($request->input('phone') !== null) {
            $requiredNotebook->phone = $request->input('phone');
        }

        if ($request->input('email') !== null) {
            $requiredNotebook->email = $request->input('email');
        }

        if ($request->input('dateOfBirth') !== null) {
            $requiredNotebook->date_of_birth = $request->input('dateOfBirth');
        }

        if ($request->input('photoAvailable') !== null) {
            $requiredNotebook->photo_available = $request->input('photoAvailable');
        }

        if ($request->input('photoAvailable')) {
            $requiredNotebook->photo_type = $request->input('photoType');
            $requiredNotebook->photo_content = base64_decode($request->input('photoContent'));
        }
        $requiredNotebook->save();
        return response()->json(['message' => 'The record edited successfully'], 200);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $requiredNotebook = Notebook::find($id);
        if (!$requiredNotebook) {
            return response()->json([
                'message' => 'Record not found',
            ], 404);
        }
        $requiredNotebook->delete();
        return response()->json(['message' => 'The record deleted successfully'], 200);
    }
}
