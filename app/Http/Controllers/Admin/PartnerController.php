<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PartnerDiscount;
use App\Models\User;
use App\Settings\LocaleSettings;
use App\Settings\ReferralSettings;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    const READ_PERMISSION = "admin.partners.read";
    const WRITE_PERMISSION = "admin.partners.write";
    public function index(LocaleSettings $locale_settings)
    {
        $this->checkAnyPermission([self::WRITE_PERMISSION,self::READ_PERMISSION]);

        return view('admin.partners.index', [
            'locale_datatables' => $locale_settings->datatables
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create()
    {
        $this->checkPermission(self::WRITE_PERMISSION);

        return view('admin.partners.create', [
            'partners' => PartnerDiscount::get(),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|min:0',
            'partner_discount' => 'required|integer|max:100|min:0',
            'registered_user_discount' => 'required|integer|max:100|min:0',
        ]);

        if(PartnerDiscount::where("user_id",$request->user_id)->exists()){
            return redirect()->route('admin.partners.index')->with('error', __('Partner already exists'));
        }

        PartnerDiscount::create($request->all());

        return redirect()->route('admin.partners.index')->with('success', __('partner has been created!'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Partner  $partner
     * @return Application|Factory|View
     */
    public function edit(PartnerDiscount $partner)
    {
        $this->checkPermission(self::WRITE_PERMISSION);

        return view('admin.partners.edit', [
            'partners' => PartnerDiscount::get(),
            'partner' => $partner,
            'users' => User::orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  Partner  $partner
     * @return RedirectResponse
     */
    public function update(Request $request, PartnerDiscount $partner)
    {
        //dd($request);
        $request->validate([
            'user_id' => 'required|integer|min:0',
            'partner_discount' => 'required|integer|max:100|min:0',
            'registered_user_discount' => 'required|integer|max:100|min:0',
        ]);

        $partner->update($request->all());

        return redirect()->route('admin.partners.index')->with('success', __('partner has been updated!'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Partner  $partner
     * @return RedirectResponse
     */
    public function destroy(PartnerDiscount $partner)
    {
        $this->checkPermission(self::WRITE_PERMISSION);

        $partner->delete();

        return redirect()->back()->with('success', __('partner has been removed!'));
    }



    public function dataTable()
    {
        $query = PartnerDiscount::query();

        return datatables($query)
            ->addColumn('actions', function (PartnerDiscount $partner) {
                return '
                            <a data-content="'.__('Edit').'" data-toggle="popover" data-trigger="hover" data-placement="top" href="'.route('admin.partners.edit', $partner->id).'" class="mr-1 btn btn-sm btn-info"><i class="fas fa-pen"></i></a>
                           <form class="d-inline" onsubmit="return submitResult();" method="post" action="'.route('admin.partners.destroy', $partner->id).'">
                            '.csrf_field().'
                            '.method_field('DELETE').'
                           <button data-content="'.__('Delete').'" data-toggle="popover" data-trigger="hover" data-placement="top" class="mr-1 btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                       </form>
                ';
            })
            ->addColumn('user', function (PartnerDiscount $partner) {
                return ($user = User::where('id', $partner->user_id)->first()) ? '<a href="'.route('admin.users.show', $partner->user_id) . '">' . $user->name . '</a>' : __('Unknown user');
            })
            ->editColumn('created_at', function (PartnerDiscount $partner) {
                return $partner->created_at ? $partner->created_at->diffForHumans() : '';
            })
            ->editColumn('partner_discount', function (PartnerDiscount $partner) {
                return $partner->partner_discount ? $partner->partner_discount . '%' : '0%';
            })
            ->editColumn('registered_user_discount', function (PartnerDiscount $partner) {
                return $partner->registered_user_discount ? $partner->registered_user_discount . '%' : '0%';
            })
            ->editColumn('referral_system_commission', function (PartnerDiscount $partner, ReferralSettings $referral_settings) {
                return $partner->referral_system_commission >= 0 ? $partner->referral_system_commission . '%' : __('Default') . ' ('.$referral_settings->percentage . '%)';
            })
            ->orderColumn('user', 'user_id $1')
            ->rawColumns(['user', 'actions'])
            ->make();
    }
}
