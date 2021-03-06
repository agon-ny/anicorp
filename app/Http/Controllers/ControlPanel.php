<?php

namespace App\Http\Controllers;

use App\Models\Anime;
use App\Models\Ep;
use App\Models\User;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ControlPanel extends Controller
{
    // Showing login page for control panel
    public function login() {
        return view('controlPanel.login');
    }

    // Showing home page for control panel
    public function home() {
        return view('controlPanel.home');
    }

    // Showing animes page for control page
    public function animes() {
        return view('controlPanel.animes', ['animes' => Anime::latest()->get()]);
    }

    // Showing new anime page for control page
    public function newAnime() {
        return view('controlPanel.newAnime');
    }

    // Showing anime edit page for control page
    public function animeEdit(Anime $anime) {
        return view('controlPanel.editAnime', ['anime' => $anime]);
    }

    // Authenticating a new administrator
    public function authenticate(Request $request) {
        $secureInfo = $request->validate([
            'email' => ['required','email'],
            'password' => ['required']
        ]);
        $user = User::where('email', $secureInfo['email'])->get();

        if($user[0]->admin == False) {
            return back()->with('message', 'Only admins can login!');
        }
 

        if(auth()->attempt($secureInfo, $request['remember'])){
            $request->session()->regenerate();
            return redirect('/controlpanel/home')->with('message', 'You are logged in!');
        };

        return back()->withErrors(['email' => 'Some informations are wrong :('])->onlyInput('email');
    }

    // Logging a user out
    public function logout(Request $request) {
        auth()->logout();
        $request->session()->invalidate();

        return redirect('/controlpanel');
    }

    // Storing a new anime
    public function animeStore(Request $request) {
        $secureInfo = $request->validate([
            'name' => ['required', 'max:255', 'min:3'],
            'description' => ['required'],
            'animeCover' => ['required'],
            'animeBackground' => ['required']
        ]);

        $secureInfo['animeCover'] = $request->file('animeCover')->store('animeCovers', 'public');
        $secureInfo['animeBackground'] = $request->file('animeBackground')->store('animeBackgrounds', 'public');

        Anime::create($secureInfo);
        return redirect('/controlpanel/animes')->with('message', 'A new anime was added!');
    }

    // Deleting an anime
    public function animeDelete(Anime $anime){
        //Deleting anime cover image
        Storage::disk('public')->delete($anime->animeCover);
        //Deleting anime background image
        Storage::disk('public')->delete($anime->animeBackground);

        //Deleting anime record from database
        $anime->delete();

        return redirect('/controlpanel/animes')->with('message', $anime->name.' was deleted :(');
    }

    // Updating an anime
    public function animeUpdate(Request $request,Anime $anime) {
        $secureInfo = $request->validate([
            'name' => ['required'],
            'description' => ['required'],
        ]);

        if($request->hasFile('animeCover')){
            //Deleting anime cover image
            Storage::disk('public')->delete($anime->animeCover);
            $secureInfo['animeCover'] = $request->file('animeCover')->store('animeCovers', 'public');
        }

        if($request->hasFile('animeBackground')){
            //Deleting anime background image
            Storage::disk('public')->delete($anime->animeBackground);
            $secureInfo['animeBackground'] = $request->file('animeBackground')->store('animeBackgrounds', 'public');
        }

        $anime->update($secureInfo);

        return redirect('/controlpanel/animes')->with('message', 'An anime was updated');
    }

    // Showing page to control eps
    public function eps(Anime $anime) {
        return view('controlPanel.eps', ['anime' => $anime, 'eps' => $anime->ep()->get()]);
    }

    // Showing form to add a new ep
    public function newEp(Anime $anime) {
        return view('controlPanel.newEp', ['anime' => $anime]);
    }

    // Storing a new ep
    public function storeEp(Request $request, Anime $anime) {
        $secureInfo = $request->validate([
            'name' => ['required', 'min:2', 'max:255'],
            'ep' => ['required'],
            'watch' => ['required']
        ]);

        $anime->ep()->create($secureInfo);

        return redirect('/controlpanel/'.$anime->id.'/eps')->with('message', 'A new ep was added!');

    }

    // Deleting an ep
    public function deleteEp(Anime $anime,Ep $ep) {
        $ep->delete();
        return back()->with('message', 'An ep was deleted!');
    }

    // Showing form to update an ep
    public function epEdit(Anime $anime, Ep $ep) {
        return view('controlPanel.editEp', ['ep' => $ep, 'anime' => $anime]);
    }

    // Updating an ep
    public function epUpdate(Request $request, Anime $anime, Ep $ep) {
        $secureInfo = $request->validate([
            'name' => ['required'],
            'ep' => ['required'],
            'watch' => ['required']
        ]);

        $ep->update($secureInfo);

        return back()->with('message', 'This ep was updated successfully!');
    }

}
