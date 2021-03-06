<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Session;
use App\Distributor;
use App\Store;
use App\AuditTemplate;
use App\GradeMatrix;
use App\FormCategory;
use App\SosTagging;
use App\StoreSosTag;

class StoreController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $stores = Store::with('account')
            ->with('customer')
            ->with('region')
            ->with('distributor')
            ->with('audittemplate')
            ->with('gradematrix')
            ->get();
        return view('store.index',compact('stores'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $distributors = Distributor::getLists();
        $audittemplates = AuditTemplate::getLists();
        $passings = GradeMatrix::getLists();
        $categories = FormCategory::sosTagging();
        $sostags = SosTagging::all();
        return view('store.create',compact('distributors', 'audittemplates', 'passings', 'categories', 'sostags'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        dd($request->all());
        // $this->validate($request, [
        //     'store' => 'required|max:100|unique_with:stores, store_code = store_code, distributor = distributor_id',
        //     'store_code' => 'required|not_in:0',
        //     'distributor' => 'required|not_in:0'
        // ]);

        // \DB::beginTransaction();

        // try {
        //     $store = new Store;
        //     $store->distributor_id = $request->distributor;
        //     $store->store_code = $request->store_code;
        //     $store->store = $request->store;
        //     $store->save();

        //     \DB::commit();

        //     Session::flash('flash_message', 'Store successfully added!');
        //     return redirect()->route("store.index");

        // } catch (Exception $e) {
        //     DB::rollBack();
        //     return redirect()->back();
        // }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $store = Store::findOrFail($id);

        $distributors = Distributor::getLists();
        $audittemplates = AuditTemplate::getLists();
        $passings = GradeMatrix::getLists();
        $categories = FormCategory::sosTagging();
        $sostags = SosTagging::all();
        $storesos = StoreSosTag::where('store_id',$store->id)->get();

        return view('store.edit',compact('store', 'distributors', 'audittemplates', 'passings', 'categories', 'sostags', 'storesos'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        // dd($request->all());
        $this->validate($request, [
            'store' => 'required|max:100|unique_with:stores, store_code = store_code,'.$id,
            'store_code' => 'required',
            'distributor' => 'required|not_in:0',
            'template' => 'required|not_in:0',
            'passing' => 'required|not_in:0'
        ]);

        \DB::beginTransaction();

        try {
            $store = Store::findOrFail($id);
            $store->distributor_id = $request->distributor;
            $store->store_code = $request->store_code;
            $store->store = $request->store;
            $store->grade_matrix_id = $request->passing;
            $store->audit_template_id = $request->template;
            $store->update();

            StoreSosTag::where('store_id', $store->id)->delete();

            if(!empty($request->cat)){
                foreach ($request->cat as $key => $value) {
                    $data[] = ['store_id' => $store->id, 'form_category_id' => $key, 'sos_tag_id' => $value];
                }

                StoreSosTag::insert($data);
            }
            

            \DB::commit();

            Session::flash('flash_message', 'Store successfully updated!');
            return redirect()->route("store.edit",[$id]);

        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
