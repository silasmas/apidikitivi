<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\LegalInfoTitle;
use App\Models\LegalInfoSubject;
use App\Http\Resources\LegalInfoSubject as ResourcesLegalInfoSubject;
use App\Models\LegalInfoContent;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class LegalInfoSubjectController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $legal_info_subjects = LegalInfoSubject::orderByDesc('created_at')->get();

        return $this->handleResponse(ResourcesLegalInfoSubject::collection($legal_info_subjects), __('notifications.find_all_legal_info_subjects_success'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Get inputs
        $inputs = [
            'subject_name' => [
                'en' => $request->subject_name_en,
                'fr' => $request->subject_name_fr,
                'ln' => $request->subject_name_ln
            ],
            'subject_description' => [
                'en' => $request->subject_description_en,
                'fr' => $request->subject_description_fr,
                'ln' => $request->subject_description_ln
            ]
        ];

        // Validate required fields
        if (trim($inputs['subject_name']['en']) == null AND trim($inputs['subject_name']['fr']) == null AND trim($inputs['subject_name']['ln']) == null) {
            return $this->handleError($inputs['subject_name'], __('validation.required'), 400);
        }

        $legal_info_subject = LegalInfoSubject::create($inputs);

        return $this->handleResponse(new ResourcesLegalInfoSubject($legal_info_subject), __('notifications.create_legal_info_subject_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $legal_info_subject = LegalInfoSubject::find($id);

        if (is_null($legal_info_subject)) {
            return $this->handleError(__('notifications.find_legal_info_subject_404'));
        }

        return $this->handleResponse(new ResourcesLegalInfoSubject($legal_info_subject), __('notifications.find_legal_info_subject_success'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\LegalInfoSubject  $legal_info_subject
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, LegalInfoSubject $legal_info_subject)
    {
        // Get inputs
        $inputs = [
            'id' => $request->id,
            'subject_name' => [
                'en' => $request->subject_name_en,
                'fr' => $request->subject_name_fr,
                'ln' => $request->subject_name_ln
            ],
            'subject_description' => [
                'en' => $request->subject_description_en,
                'fr' => $request->subject_description_fr,
                'ln' => $request->subject_description_ln
            ]
        ];
        // Select all subjects current subject to check unique constraint
        $legal_info_subjects = LegalInfoSubject::all();
        $current_legal_info_subject = LegalInfoSubject::find($inputs['id']);

        if ($inputs['subject_name']['en'] != null) {
            foreach ($legal_info_subjects as $another_legal_info_subject):
                if ($current_legal_info_subject->subject_name->en != $inputs['subject_name']['en']) {
                    if ($another_legal_info_subject->subject_name->en == $inputs['subject_name']['en']) {
                        return $this->handleError($inputs['subject_name']['en'], __('validation.custom.subject.exists'), 400);
                    }
                }
            endforeach;
    
            $legal_info_subject->update([
                'subject_name' => [
                    'en' => $request->subject_name_en
                ],
                'updated_at' => now()
            ]);
        }

        if ($inputs['subject_name']['fr'] != null) {
            foreach ($legal_info_subjects as $another_legal_info_subject):
                if ($current_legal_info_subject->subject_name->fr != $inputs['subject_name']['fr']) {
                    if ($another_legal_info_subject->subject_name->fr == $inputs['subject_name']['fr']) {
                        return $this->handleError($inputs['subject_name']['fr'], __('validation.custom.subject.exists'), 400);
                    }
                }
            endforeach;
    
            $legal_info_subject->update([
                'subject_name' => [
                    'fr' => $request->subject_name_fr
                ],
                'updated_at' => now()
            ]);
        }

        if ($inputs['subject_name']['ln'] != null) {
            foreach ($legal_info_subjects as $another_legal_info_subject):
                if ($current_legal_info_subject->subject_name->ln != $inputs['subject_name']['ln']) {
                    if ($another_legal_info_subject->subject_name->ln == $inputs['subject_name']['ln']) {
                        return $this->handleError($inputs['subject_name']['ln'], __('validation.custom.subject.exists'), 400);
                    }
                }
            endforeach;
    
            $legal_info_subject->update([
                'subject_name' => [
                    'ln' => $request->subject_name_ln
                ],
                'updated_at' => now()
            ]);
        }

        if ($inputs['subject_description']['en'] != null) {
            $legal_info_subject->update([
                'subject_description' => [
                    'en' => $request->subject_description_en
                ],
                'updated_at' => now()
            ]);
        }

        if ($inputs['subject_description']['fr'] != null) {
            $legal_info_subject->update([
                'subject_description' => [
                    'fr' => $request->subject_description_fr
                ],
                'updated_at' => now()
            ]);
        }

        if ($inputs['subject_description']['ln'] != null) {
            $legal_info_subject->update([
                'subject_description' => [
                    'ln' => $request->subject_description_ln
                ],
                'updated_at' => now()
            ]);
        }

        return $this->handleResponse(new ResourcesLegalInfoSubject($legal_info_subject), __('notifications.update_legal_info_subject_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\LegalInfoSubject  $legal_info_subject
     * @return \Illuminate\Http\Response
     */
    public function destroy(LegalInfoSubject $legal_info_subject)
    {
        $legal_info_subject->delete();

        $legal_info_subjects = LegalInfoSubject::all();

        return $this->handleResponse(ResourcesLegalInfoSubject::collection($legal_info_subjects), __('notifications.delete_legal_info_subject_success'));
    }

    // ==================================== CUSTOM METHODS ====================================
    /**
     * Find by subject name.
     *
     * @param  string  $locale
     * @param  string  $data
     * @return \Illuminate\Http\Response
     */
    public function search($locale, $data)
    {
        $legal_info_subject = LegalInfoSubject::where('subject_name->' . $locale, $data)->first();

        if (is_null($legal_info_subject)) {
            return $this->handleError(__('notifications.find_legal_info_subject_404'));
        }

        return $this->handleResponse(new ResourcesLegalInfoSubject($legal_info_subject), __('notifications.find_legal_info_subject_success'));
    }

    /**
     * Register data for about / terms_of_use / privacy_policy / faq.
     *
     * @param  string  $subject
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function registerSubject($subject, Request $request)
    {
        if ($subject == 'about') {
            $legal_info_subject = LegalInfoSubject::create([
                'subject_name' => [
                    'en' => 'About us',
                    'fr' => 'A propos de nous',
                    'ln' => 'Oyo etali biso'
                ],
                'subject_description' => [
                    'en' => 'DikiTivi, your Christian TV channel for all the programs of your choice.',
                    'fr' => 'DikiTivi, votre chaine TV chrétienne pour tous les programmes de votre choix.',
                    'ln' => 'DikiTivi, chaîne na yo ya TV chrétienne pona ba émissions nionso oyo oponi.'
                ]    
            ]);

            foreach ($request->titles_fr as $key_t => $title_fr) {
                $legal_info_title = LegalInfoTitle::create([
                    'title' => [
                        'en' => $title_fr,
                        'fr' => $request->titles_fr[$key_t],
                        'ln' => $request->titles_ln[$key_t]
                    ],
                    'legal_info_subject_id' => $legal_info_subject->id
                ]);

                foreach ($request->subtitles_fr as $key_c => $title_fr) {
                    LegalInfoContent::create([
                        'subtitle' => [
                            'en' => $request->subtitles_en[$key_c],
                            'fr' => $request->subtitles_fr[$key_c],
                            'ln' => $request->subtitles_ln[$key_c]
                        ],
                        'content' => [
                            'en' => $request->contents_en[$key_c],
                            'fr' => $request->contents_fr[$key_c],
                            'ln' => $request->contents_ln[$key_c]
                        ],
                        'video_url' => $request->videos_url[$key_c],
                        'legal_info_title_id' => $legal_info_title->id
                    ]);
                }
            }

            return $this->handleResponse(new ResourcesLegalInfoSubject($legal_info_subject), __('notifications.create_legal_info_subject_success'));
        }

        if ($subject == 'terms_of_use') {
            $legal_info_subject = LegalInfoSubject::create([
                'subject_name' => [
                    'en' => 'Terms of use',
                    'fr' => 'Conditions d\'utilisation',
                    'ln' => 'Mibeko ya kosalela'
                ],
                'subject_description' => [
                    'en' => 'By using DikiTivi, you agree to our terms of use. So we suggest you take some time to read these conditions, because you are about to enter into a binding contract.',
                    'fr' => 'En utilisant DikiTivi, vous acceptez nos conditions d\'utilisation. Alors nous vous suggérons de prendre un peu de votre temps pour lire ces conditions, car vous êtes sur le point de conclure un contrat qui vous engage.',
                    'ln' => 'Na kosalela DikiTivi, ozali kondima mibeko na biso ya kosalela. Yango wana tosengi ete ozwa mwa ntango mpo na kotánga makambo yango, mpamba te ozali pene ya kosala kontra oyo ekokangama.'
                ]    
            ]);

            foreach ($request->titles_fr as $key_t => $title_fr) {
                $legal_info_title = LegalInfoTitle::create([
                    'title' => [
                        'en' => $title_fr,
                        'fr' => $request->titles_fr[$key_t],
                        'ln' => $request->titles_ln[$key_t]
                    ],
                    'legal_info_subject_id' => $legal_info_subject->id
                ]);

                foreach ($request->subtitles_fr as $key_c => $title_fr) {
                    LegalInfoContent::create([
                        'subtitle' => [
                            'en' => $request->subtitles_en[$key_c],
                            'fr' => $request->subtitles_fr[$key_c],
                            'ln' => $request->subtitles_ln[$key_c]
                        ],
                        'content' => [
                            'en' => $request->contents_en[$key_c],
                            'fr' => $request->contents_fr[$key_c],
                            'ln' => $request->contents_ln[$key_c]
                        ],
                        'video_url' => $request->videos_url[$key_c],
                        'legal_info_title_id' => $legal_info_title->id
                    ]);
                }
            }

            return $this->handleResponse(new ResourcesLegalInfoSubject($legal_info_subject), __('notifications.create_legal_info_subject_success'));
        }

        if ($subject == 'privacy_policy') {
            $legal_info_subject = LegalInfoSubject::create([
                'subject_name' => [
                    'en' => 'Privacy policy',
                    'fr' => 'Politique de confidentialité',
                    'ln' => 'Politiki ya kobatela makambo ya moto'
                ],
                'subject_description' => [
                    'en' => 'We have simplified the wording of our privacy policy, so that you stay informed when it comes to controlling your personal data.',
                    'fr' => 'Nous avons simplifié la rédaction de notre politique de confidentialité, afin que vous restiez informé en ce qui concerne le contrôle de vos données personnelles.',
                    'ln' => 'Tosali ete maloba ya politiki na biso ya kobatela makambo ya moto ye moko ezala pɛtɛɛ, mpo oyeba makambo soki etali kotambwisa makambo na yo.'
                ]    
            ]);

            foreach ($request->titles_fr as $key_t => $title_fr) {
                $legal_info_title = LegalInfoTitle::create([
                    'title' => [
                        'en' => $title_fr,
                        'fr' => $request->titles_fr[$key_t],
                        'ln' => $request->titles_ln[$key_t]
                    ],
                    'legal_info_subject_id' => $legal_info_subject->id
                ]);

                foreach ($request->subtitles_fr as $key_c => $title_fr) {
                    LegalInfoContent::create([
                        'subtitle' => [
                            'en' => $request->subtitles_en[$key_c],
                            'fr' => $request->subtitles_fr[$key_c],
                            'ln' => $request->subtitles_ln[$key_c]
                        ],
                        'content' => [
                            'en' => $request->contents_en[$key_c],
                            'fr' => $request->contents_fr[$key_c],
                            'ln' => $request->contents_ln[$key_c]
                        ],
                        'video_url' => $request->videos_url[$key_c],
                        'legal_info_title_id' => $legal_info_title->id
                    ]);
                }
            }

            return $this->handleResponse(new ResourcesLegalInfoSubject($legal_info_subject), __('notifications.create_legal_info_subject_success'));
        }

        if ($subject == 'faq') {
            $legal_info_subject = LegalInfoSubject::create([
                'subject_name' => [
                    'en' => 'FAQ',
                    'fr' => 'FAQ',
                    'ln' => 'MBM'
                ],
                'subject_description' => [
                    'en' => 'Here you will find frequently asked questions from users of our services and the answers you will need. In case you can\'t find your answers, you can ask the question yourself, and you will get the answer shortly.',
                    'fr' => 'Ici, vous trouverez les questions fréquemment posées par les utilisateurs de nos services et les réponses dont vous aurez besoin. Au cas où vous ne trouvez pas vos réponses, vous pouvez poser vous-même la question, et vous aurez la réponse à bref délai.',
                    'ln' => 'Awa okokuta mituna oyo batunaka mingi epai ya basaleli ya misala na biso mpe biyano oyo okozala na mposa na yango. Soki ozali kozwa biyano na yo te, okoki kotuna motuna yango yo moko, mpe okozwa eyano mwa moke.'
                ]    
            ]);

            foreach ($request->titles_fr as $key_t => $title_fr) {
                $legal_info_title = LegalInfoTitle::create([
                    'title' => [
                        'en' => $title_fr,
                        'fr' => $request->titles_fr[$key_t],
                        'ln' => $request->titles_ln[$key_t]
                    ],
                    'legal_info_subject_id' => $legal_info_subject->id
                ]);

                foreach ($request->subtitles_fr as $key_c => $title_fr) {
                    LegalInfoContent::create([
                        'subtitle' => [
                            'en' => $request->subtitles_en[$key_c],
                            'fr' => $request->subtitles_fr[$key_c],
                            'ln' => $request->subtitles_ln[$key_c]
                        ],
                        'content' => [
                            'en' => $request->contents_en[$key_c],
                            'fr' => $request->contents_fr[$key_c],
                            'ln' => $request->contents_ln[$key_c]
                        ],
                        'video_url' => $request->videos_url[$key_c],
                        'legal_info_title_id' => $legal_info_title->id
                    ]);
                }
            }

            return $this->handleResponse(new ResourcesLegalInfoSubject($legal_info_subject), __('notifications.create_legal_info_subject_success'));
        }
    }
}
