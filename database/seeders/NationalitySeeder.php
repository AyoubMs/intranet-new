<?php

namespace Database\Seeders;

use App\Models\Nationality;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NationalitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $nationalities = [
            "Marocaine", "Afghane", "Albanaise", "Algerienne", "Allemande", "Americaine", "Andorrane", "Angolaise", "Antiguaise et barbudienne", "Argentine", "Armenienne", "Australienne", "Autrichienne", "Azerbaïdjanaise", "Bahamienne", "Bahreinienne", "Bangladaise", "Barbadienne", "Belge", "Belizienne", "Beninoise", "Bhoutanaise", "Bielorusse", "Birmane", "Bissau-Guinéenne", "Bolivienne", "Bosnienne", "Botswanaise", "Bresilienne", "Britannique", "Bruneienne", "Bulgare", "Burkinabe", "Burundaise", "Cambodgienne", "Camerounaise", "Canadienne", "Cap-verdienne", "Centrafricaine", "Chilienne", "Chinoise", "Chypriote", "Colombienne", "Comorienne", "Congolaise", "Costaricaine", "Croate", "Cubaine", "Danoise", "Djiboutienne", "Dominicaine", "Dominiquaise", "Egyptienne", "Emirienne", "Equato-guineenne", "Equatorienne", "Erythreenne", "Espagnole", "Est-timoraise", "Estonienne", "Ethiopienne", "Fidjienne", "Finlandaise", "Française", "Gabonaise", "Gambienne", "Georgienne", "Ghaneenne", "Grenadienne", "Guatemalteque", "Guineenne", "Guyanienne", "Haïtienne", "Hellenique", "Hondurienne", "Hongroise", "Indienne", "Indonesienne", "Irakienne", "Irlandaise", "Islandaise", "Israélienne", "Italienne", "Ivoirienne", "Jamaïcaine", "Japonaise", "Jordanienne", "Kazakhstanaise", "Kenyane", "Kirghize", "Kiribatienne", "Kittitienne-et-nevicienne", "Kossovienne", "Koweitienne", "Laotienne", "Lesothane", "Lettone", "Libanaise", "Liberienne", "Libyenne", "Liechtensteinoise", "Lituanienne", "Luxembourgeoise", "Macedonienne", "Malaisienne", "Malawienne", "Maldivienne", "Malgache", "Malienne", "Maltaise", "Marshallaise", "Mauricienne", "Mauritanienne", "Mexicaine", "Micronesienne", "Moldave", "Monegasque", "Mongole", "Montenegrine", "Mozambicaine", "Namibienne", "Nauruane", "Neerlandaise", "Neo-zelandaise", "Nepalaise", "Nicaraguayenne", "Nigeriane", "Nigerienne", "Nord-coréenne", "Norvegienne", "Omanaise", "Ougandaise", "Ouzbeke", "Pakistanaise", "Palau", "Palestinienne", "Panameenne", "Papouane-neoguineenne", "Paraguayenne", "Peruvienne", "Philippine", "Polonaise", "Portoricaine", "Portugaise", "Qatarienne", "Roumaine", "Russe", "Rwandaise", "Saint-lucienne", "Saint-marinaise", "Saint-vincentaise-et-grenadine", "Salomonaise", "Salvadorienne", "Samoane", "Santomeenne", "Saoudienne", "Senegalaise", "Serbe", "Seychelloise", "Sierra-leonaise", "Singapourienne", "Slovaque", "Slovene", "Somalienne", "Soudanaise", "Sri-lankaise", "Sud-africaine", "Sud-coréenne", "Suedoise", "Suisse", "Surinamaise", "Swazie", "Syrienne", "Tadjike", "Taiwanaise", "Tanzanienne", "Tchadienne", "Tcheque", "Thaïlandaise", "Togolaise", "Tonguienne", "Trinidadienne", "Tunisienne", "Turkmene", "Turque", "Tuvaluane", "Ukrainienne", "Uruguayenne", "Vanuatuane", "Venezuelienne", "Vietnamienne", "Yemenite", "Zambienne", "Zimbabweenne"];

        foreach ($nationalities as $nationality) {
            Nationality::factory()->create([
                'name' => $nationality
            ]);
        }
    }
}
