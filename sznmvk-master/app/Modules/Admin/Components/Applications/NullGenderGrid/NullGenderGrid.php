<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Applications\NullGenderGrid;

use App\Classes\Mail\MessageCenter;
use App\Classes\Nette\Security\Authorizator;
use App\Components\Flashes\Flashes;
use App\Components\GridComponent\GridComponent;
use App\Model\ApplicationsModel;
use App\Model\BonusesModel;
use App\Model\FacultiesModel;
use App\Model\SessionsModel;
use App\Model\TransportModel;
use App\Model\GendersModel;
use App\Modules\Admin\Components\Applications\CreateApplicationForm\CreateApplicationForm;
use Dibi\Exception;
use Dibi\UniqueConstraintViolationException;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use Nette\Http\Session;
use Nette\Schema\ValidationException;
use Nette\Utils\ArrayHash;
use Ublaboo\DataGrid\DataGrid;

class NullGenderGrid extends GridComponent
{
    /** @var ApplicationsModel */
    protected $applicationsModel;


    /** @var GendersModel */
    protected $genderModel;

    const NAMES_MALE = ['Drahoslav', 'Severín', 'Alexej', 'Ernest', 'Rastislav', 'Radovan', 'Dobroslav',
        'Dalibor', 'Vincent', 'Miloš', 'Timotej', 'Gejza', 'Bohuš', 'Alfonz', 'Gašpar', 'Emil',
        'Erik', 'Blažej', 'Zdenko', 'Dezider', 'Arpád', 'Valentín', 'Pravoslav', 'Jaromír', 'Roman', 'Matej',
        'Frederik', 'Viktor', 'Alexander', 'Radomír', 'Albín', 'Bohumil', 'Kazimír', 'Fridrich', 'Radoslav', 'Tomáš', 'Alan',
        'Branislav', 'Bruno', 'Gregor', 'Vlastimil', 'Boleslav', 'Eduard', 'Jozef', 'Víťazoslav', 'Blahoslav', 'Beňadik', 'Adrián',
        'Gabriel', 'Marián', 'Emanuel', 'Miroslav', 'Benjamín', 'Hugo', 'Richard', 'Izidor', 'Zoltán', 'Albert', 'Igor', 'Július',
        'Aleš', 'Fedor', 'Rudolf', 'Valér', 'Marcel', 'Ervín', 'Slavomír', 'Vojtech', 'Juraj', 'Marek', 'Jaroslav',
        'Žigmund', 'Florián', 'Roland', 'Pankrác', 'Servác', 'Bonifác', 'Svetozár', 'Bernard', 'Júlia', 'Urban', 'Dušan', 'Viliam',
        'Ferdinand', 'Norbert', 'Róbert', 'Medard', 'Zlatko', 'Anton', 'Vasil', 'Vít', 'Adolf', 'Vratislav', 'Alfréd', 'Alojz', 'Ján',
        'Tadeáš', 'Ladislav', 'Peter', 'Pavol', 'Miloslav', 'Prokop', 'Cyril', 'Metod', 'Patrik', 'Oliver', 'Ivan', 'Kamil', 'Henrich',
        'Drahomír', 'Bohuslav', 'Iľja', 'Daniel', 'Vladimír', 'Jakub', 'Krištof', 'Ignác', 'Gustáv', 'Jerguš', 'Dominik', 'Oskar',
        'Vavrinec', 'Ľubomír', 'Mojmír', 'Leonard', 'Tichomír', 'Filip', 'Bartolomej', 'Ľudovít', 'Samuel', 'Augustín', 'Belo',
        'Oleg', 'Bystrík', 'Ctibor', 'Ľudomil', 'Konštantín', 'Ľuboslav', 'Matúš', 'Móric', 'Ľuboš', 'Ľubor', 'Vladislav', 'Cyprián',
        'Václav', 'Michal', 'Jarolím', 'Arnold', 'Levoslav', 'František', 'Dionýz', 'Maximilián', 'Koloman', 'Boris', 'Lukáš',
        'Kristián', 'Vendelín', 'Sergej', 'Aurel', 'Demeter', 'Denis', 'Hubert', 'Karol', 'Imrich', 'René', 'Bohumír', 'Teodor',
        'Tibor', 'Maroš', 'Martin', 'Svätopluk', 'Stanislav', 'Leopold', 'Eugen', 'Félix', 'Klement', 'Kornel', 'Milan', 'Vratko',
        'Ondrej', 'Andrej', 'Edmund', 'Oldrich', 'Oto', 'Mikuláš', 'Ambróz', 'Radúz', 'Bohdan', 'Adam', 'Štefan', 'Dávid', 'Silvester'
        ,

        'Abadon', 'Abdon', 'Ábel', 'Abelard', 'Abraham', 'Abrahám', 'Absolón', 'Adalbert', 'Adam', 'Adin', 'Adolf', 'Adrián',
        'Agaton', 'Achil', 'Alan', 'Alban', 'Albert', 'Albín', 'Albrecht', 'Aldo', 'Aleš', 'Alexandr', 'Alexej', 'Alfons',
        'Alfréd', 'Alois', 'Alva', 'Alvar', 'Alvin', 'Amadeus', 'Amand', 'Amát', 'Ambrož', 'Ámos', 'Anastáz',
        'Anatol', 'Anděl', 'Andrej', 'Anselm', 'Antal', 'Antonín', 'Aram', 'Ariel', 'Aristid', 'Arkád', 'Armand',
        'Armin', 'Arne', 'Arnold', 'Arnošt', 'Árón', 'Arpád', 'Artur', 'Artuš', 'Arzen', 'Atanas', 'Atila', 'August',
        'Aurel', 'Axel', 'Baltazar', 'Barnabáš', 'Bartoloměj', 'Bazil', 'Beatus', 'Bedřich', 'Benedikt', 'Benjamín',
        'Bernard', 'Bertold', 'Bertram', 'Bivoj', 'Blahomil', 'Blahomír', 'Blahoslav', 'Blažej', 'Bohdan', 'Bohuchval',
        'Bohumil', 'Bohumír', 'Bohuslav', 'Bohuš', 'Bojan', 'Bolemír', 'Boleslav', 'Bonifác', 'Boris', 'Bořek',
        'Bořislav', 'Bořivoj', 'Božetěch', 'Božidar', 'Božislav', 'Branimír', 'Branislav', 'Bratislav', 'Brian', 'Brit',
        'Bruno', 'Břetislav', 'Budimír', 'Budislav', 'Budivoj', 'Cecil', 'Celestýn', 'Cézar', 'Ctibor', 'Ctirad',
        'Ctislav', 'Cyprián', 'Cyril', 'Čeněk', 'Čestmír', 'Čistoslav', 'Dag', 'Dalibor', 'Dalimil', 'Dalimír',
        'Damián', 'Dan', 'Daniel', 'Darek', 'Darius', 'David', 'Denis', 'Děpold', 'Dětmar', 'Dětřich', 'Dezider',
        'Dimitrij', 'Dino', 'Diviš', 'Dluhoš', 'Dobromil', 'Dobromír', 'Dobroslav', 'Dominik', 'Donald', 'Donát',
        'Dorián', 'Drahomil', 'Drahomír', 'Drahoslav', 'Drahoš', 'Drahotín', 'Dušan', 'Edgar', 'Edmond', 'Edvard',
        'Edvín', 'Egmont', 'Egon', 'Eliáš', 'Elizej', 'Elmar', 'Elvis', 'Emanuel', 'Emerich', 'Emil', 'Engelbert',
        'Erazim', 'Erhard', 'Erik', 'Ernest', 'Ervín', 'Eusebius', 'Evald', 'Evan', 'Evarist', 'Evžen', 'Ezechiel',
        'Ezra', 'Fabián', 'Faust', 'Fedor', 'Felix', 'Ferdinand', 'Fidel', 'Filemon', 'Filibert', 'Filip', 'Filomen',
        'Flavián', 'Florentýn', 'Florián', 'Fortunát', 'František', 'Fridolín', 'Gabin', 'Gabriel', 'Gál', 'Garik',
        'Gaston', 'Gedeon', 'Genadij', 'Gerald', 'Gerard', 'Gerazim', 'Géza', 'Gilbert', 'Gleb', 'Glen', 'Gorazd',
        'Gordon', 'Gothard', 'Gracián', 'Grant', 'Gunter', 'Gustav', 'Hanuš', 'Harald', 'Haštal', 'Havel', 'Helmut',
        'Herbert', 'Heřman', 'Hilar', 'Hjalmar', 'Homér', 'Honor', 'Horác', 'Horst', 'Horymír', 'Hostimil', 'Hostislav',
        'Hostivít', 'Hovard', 'Hubert', 'Hugo', 'Hvězdoslav', 'Hyacint', 'Hynek', 'Hypolit', 'Chrabroš', 'Chranibor',
        'Chranislav', 'Chrudoš', 'Chval', 'Ignác', 'Igor', 'Ilja', 'Inocenc', 'Irenej', 'Irvin', 'Ivan', 'Ivar', 'Ivo',
        'Izaiáš', 'Izák', 'Izidor', 'Izmael', 'Jacek', 'Jáchym', 'Jakub', 'Jan', 'Jarmil', 'Jarolím', 'Jaromír',
        'Jaroslav', 'Jasoň', 'Jeremiáš', 'Jeroným', 'Jiljí', 'Jimram', 'Jindřich', 'Jiří', 'Job', 'Joel', 'Jonáš',
        'Jonatan', 'Jordan', 'Josef', 'Jošt', 'Jozue', 'Juda', 'Julián', 'Julius', 'Justýn', 'Kajetán', 'Kamil', 'Karel',
        'Kasián', 'Kastor', 'Kašpar', 'Kazimír', 'Kilián', 'Kim', 'Klement', 'Knut', 'Koloman', 'Kolombín', 'Konrád',
        'Konstantýn', 'Kornel', 'Kosma', 'Krasomil', 'Krasoslav', 'Kristián', 'Kryšpín', 'Kryštof', 'Křesomysl', 'Kurt',
        'Květoslav', 'Květoš', 'Kvido', 'Ladislav', 'Lambert', 'Lars', 'Laurenc', 'Lazar', 'Leandr', 'Leo', 'Leodegar',
        'Leonard', 'Leonid', 'Leontýn', 'Leopold', 'Leoš', 'Lešek', 'Lev', 'Libor', 'Liboslav', 'Lionel', 'Livius',
        'Lotar', 'Lubomír', 'Lubor', 'Luboš', 'Lucián', 'Luděk', 'Ludivoj', 'Ludomír', 'Ludoslav', 'Ludvík', 'Lukáš',
        'Lukrecius', 'Lumír', 'Lutobor', 'Magnus', 'Makar', 'Manfréd', 'Mansvet', 'Manuel', 'Marcel', 'Marek', 'Marián',
        'Marin', 'Mario', 'Martin', 'Matěj', 'Matouš', 'Matyáš', 'Max', 'Maxmilián', 'Mečislav', 'Medard', 'Melichar', 'Merlin',
        'Mervin', 'Metod', 'Michal', 'Mikoláš', 'Milan', 'Milíč', 'Milivoj', 'Miloň', 'Milorad', 'Miloslav', 'Miloš',
        'Milota', 'Milouš', 'Milovan', 'Mirek', 'Miromil', 'Miron', 'Miroslav', 'Mlad', 'Mnata', 'Mnislav', 'Modest',
        'Mojmír', 'Mojžíš', 'Morgan', 'Moric', 'Mstislav', 'Myrtil', 'Napoleon', 'Narcis', 'Natan', 'Natanael',
        'Něhoslav', 'Neklan', 'Nepomuk', 'Nezamysl', 'Nikita', 'Nikodém', 'Nikola', 'Norbert', 'Norman', 'Odolen',
        'Odon', 'Oktavián', 'Olaf', 'Olbram', 'Oldřich', 'Oleg', 'Oliver', 'Omar', 'Ondřej', 'Orest', 'Oskar', 'Osvald',
        'Ota', 'Otakar', 'Otmar', 'Ovidius', 'Palmiro', 'Pankrác', 'Pantaleon', 'Paris', 'Parsival', 'Paskal', 'Patrik',
        'Pavel', 'Pelhřim', 'Perikles', 'Petr', 'Petronius', 'Pius', 'Platón', 'Polykarp', 'Pravdomil', 'Prokop',
        'Prosper', 'Přemysl', 'Přibyslav', 'Radek', 'Radhost', 'Radim', 'Radivoj', 'Radmil', 'Radomír', 'Radoslav',
        'Radovan', 'Radúz', 'Rafael', 'Raimund', 'Rainald', 'Rainer', 'Rainhard', 'Rajko', 'Ralf', 'Ramon', 'Randolf',
        'Ranek', 'Ratibor', 'Ratmír', 'Redmond', 'Remig', 'Remus', 'Renát', 'René', 'Richard', 'Robert', 'Robin',
        'Robinson', 'Rodan', 'Roderik', 'Roger', 'Roch', 'Roland', 'Rolf', 'Roman', 'Romeo', 'Romuald', 'Romul',
        'Ronald', 'Rostislav', 'Ruben', 'Rudolf', 'Rufus', 'Rupert', 'Ruslan', 'Řehoř', 'Sámo', 'Samson', 'Samuel',
        'Saturnin', 'Saul', 'Sáva', 'Sebastián', 'Sedrik', 'Serafín', 'Serenus', 'Sergej', 'Servác', 'Severín', 'Sidon',
        'Sigfríd', 'Silván', 'Silvestr', 'Simeon', 'Sinkler', 'Sixt', 'Slávek', 'Slaviboj', 'Slavoj', 'Slavomil',
        'Slavomír', 'Smil', 'Soběslav', 'Sokrat', 'Soter', 'Spytihněv', 'Stanimír', 'Stanislav', 'Stojan', 'Stojmír',
        'Svatobor', 'Svatomír', 'Svatopluk', 'Svatoslav', 'Sven', 'Svetozar', 'Šalomoun', 'Šavel', 'Šimon', 'Šťasta',
        'Štěpán', 'Tadeáš', 'Tankred', 'Taras', 'Teobald', 'Teodor', 'Teodorik', 'Teodoz', 'Teofan', 'Teofil', 'Terenc',
        'Tiber', 'Tibor', 'Tichomil', 'Tichomír', 'Tichon', 'Timon', 'Timotej', 'Timur', 'Titus', 'Tobiáš', 'Tomáš',
        'Tomislav', 'Torkvát', 'Torsten', 'Tristan', 'Udo', 'Ulrich', 'Upton', 'Urban', 'Uve', 'Václav', 'Vadim',
        'Valdemar', 'Valentýn', 'Valerián', 'Valtr', 'Vasil', 'Vavřinec', 'Veleslav', 'Velimír', 'Věnceslav',
        'Vendelín', 'Verner', 'Věroslav', 'Vidor', 'Viktor', 'Vilém', 'Vilibald', 'Vilmar', 'Vincenc', 'Virgil',
        'Virgin', 'Vít', 'Vítězslav', 'Vitold', 'Vivian', 'Vladan', 'Vladimír', 'Vladislav', 'Vladivoj', 'Vlastimil',
        'Vlastimír', 'Vlastislav', 'Vlk', 'Vojen', 'Vojmil', 'Vojmír', 'Vojslav', 'Vojtěch', 'Volfgang', 'Vratislav',
        'Vsevolod', 'Všebor', 'Všerad', 'Všeslav', 'Záboj', 'Zachar', 'Záviš', 'Zbyhněv', 'Zbyněk', 'Zbyslav', 'Zdeněk',
        'Zderad', 'Zdislav', 'Zeno', 'Zikmund', 'Zlatan', 'Zlatomír', 'Zoltán', 'Zoran', 'Zoroslav', 'Zosim', 'Zvonimír',
        'Žarko', 'Ždan', 'Želibor', 'Želimír', 'Želislav', 'Žitomír', 'Žitoslav', 'Živan'];

    
    const NAMES_FEMALE = ['Alexandra', 'Karina', 'Daniela', 'Andrea', 'Antónia', 'Bohuslava', 'Dáša', 'Malvína',
        'Kristína', 'Nataša', 'Bohdana', 'Drahomíra', 'Sára', 'Zora', 'Tamara', 'Ema', 'Tatiana', 'Erika', 'Veronika',
        'Agáta', 'Dorota', 'Vanda', 'Zoja', 'Gabriela', 'Perla', 'Ida', 'Liana', 'Miloslava', 'Vlasta', 'Lívia',
        'Eleonóra', 'Etela', 'Romana', 'Zlatica', 'Anežka', 'Bohumila', 'Františka', 'Angela', 'Matilda', 'Svetlana',
        'Ľubica', 'Alena', 'Soňa', 'Vieroslava', 'Zita', 'Miroslava', 'Irena', 'Milena', 'Estera', 'Justína', 'Dana',
        'Danica', 'Jela', 'Jaroslava', 'Jarmila', 'Lea', 'Anastázia', 'Galina', 'Lesana', 'Hermína', 'Monika', 'Ingrida',
        'Viktória', 'Blažena', 'Žofia', 'Sofia', 'Gizela', 'Viola', 'Gertrúda', 'Zina', 'Júlia', 'Juliana', 'Želmíra',
        'Ela', 'Vanesa', 'Iveta', 'Vilma', 'Petronela', 'Žaneta', 'Xénia', 'Karolína', 'Lenka', 'Laura', 'Stanislava',
        'Margaréta', 'Dobroslava', 'Blanka', 'Valéria', 'Paulína', 'Sidónia', 'Adriána', 'Beáta', 'Petra', 'Melánia', 'Diana',
        'Berta', 'Patrícia', 'Lujza', 'Amália', 'Milota', 'Nina', 'Margita', 'Kamila', 'Dušana', 'Magdaléna', 'Oľga', 'Anna',
        'Hana', 'Božena', 'Marta', 'Libuša', 'Božidara', 'Dominika', 'Hortenzia', 'Jozefína', 'Štefánia', 'Ľubomíra',
        'Zuzana', 'Darina', 'Marcela', 'Milica', 'Elena', 'Helena', 'Lýdia', 'Anabela', 'Jana', 'Silvia', 'Nikola', 'Ružena',
        'Nora', 'Drahoslava', 'Linda', 'Melinda', 'Rebeka', 'Rozália', 'Regína', 'Alica', 'Marianna', 'Miriama', 'Martina',
        'Mária', 'Jolana', 'Ľudomila', 'Ľudmila', 'Olympia', 'Eugénia', 'Ľuboslava', 'Zdenka', 'Edita', 'Michaela',
        'Stela', 'Viera', 'Natália', 'Eliška', 'Brigita', 'Valentína', 'Terézia', 'Vladimíra', 'Hedviga', 'Uršuľa',
        'Alojza', 'Kvetoslava', 'Sabína', 'Dobromila', 'Klára', 'Simona', 'Aurélia', 'Denisa', 'Renáta', 'Irma',
        'Agnesa', 'Klaudia', 'Alžbeta', 'Elvíra', 'Cecília', 'Emília', 'Katarína', 'Henrieta', 'Bibiána', 'Barbora',
        'Marína', 'Izabela', 'Hilda', 'Otília', 'Lucia', 'Branislava', 'Bronislava', 'Ivica', 'Albína', 'Kornélia',
        'Sláva', 'Slávka', 'Judita', 'Dagmara', 'Adela', 'Nadežda', 'Eva', 'Filoména', 'Ivana', 'Milada'
        ,
        'Abigail', 'Ada', 'Adalberta', 'Adéla', 'Adelaida', 'Adina', 'Adolfa', 'Adriana', 'Afra', 'Agáta', 'Aglaja',
        'Aida', 'Alana', 'Albena', 'Alberta', 'Albína', 'Alena', 'Aleška', 'Alexandra', 'Alfréda', 'Alice', 'Alida',
        'Alina', 'Alma', 'Aloisie', 'Alžběta', 'Amálie', 'Amanda', 'Amáta', 'Anabela', 'Anastázie', 'Anatázie',
        'Anatólie', 'Anděla', 'Andrea', 'Aneta', 'Anežka', 'Angelika', 'Anita', 'Anna', 'Anselma', 'Antonie', 'Apolena',
        'Arabela', 'Aranka', 'Areta', 'Ariana', 'Ariela', 'Arleta', 'Armida', 'Arna', 'Arnolda', 'Arnoštka', 'Astrid',
        'Atanázie', 'Augusta', 'Aurélie', 'Aurora', 'Babeta', 'Barbora', 'Beáta', 'Beatrice', 'Bedřiška', 'Bela', 'Běla',
        'Belinda', 'Benedikta', 'Berenika', 'Bernarda', 'Berta', 'Bertolda', 'Bianka', 'Bibiana', 'Birgit', 'Blahomila',
        'Blahomíra', 'Blahoslava', 'Blanka', 'Blažena', 'Bohdana', 'Bohumila', 'Bohumíra', 'Bohuna', 'Bohuslava',
        'Bojana', 'Bojislava', 'Boleslava', 'Bořislava', 'Božena', 'Božetěcha', 'Božidara', 'Branimíra', 'Bratislava',
        'Brenda', 'Brigita', 'Brita', 'Bronislava', 'Bruna', 'Brunhilda', 'Břetislava', 'Cecílie', 'Celestýna', 'Celie',
        'Celina', 'Ctibora', 'Ctirada', 'Ctislava', 'Cyntie', 'Cyrila', 'Čeňka', 'Čestmíra', 'Čistoslava', 'Dagmar',
        'Dalibora', 'Dalida', 'Dalie', 'Dalila', 'Dalimila', 'Dalimíra', 'Damaris', 'Damiána', 'Dana', 'Danica',
        'Daniela', 'Danuta', 'Darie', 'Darina', 'Davida', 'Debora', 'Delie', 'Denisa', 'Diana', 'Dina', 'Dita', 'Diviška',
        'Dobrava', 'Dobromila', 'Dobromíra', 'Dobroslava', 'Dominika', 'Donalda', 'Donáta', 'Dora', 'Doris', 'Doubravka',
        'Drahomila', 'Drahomíra', 'Drahoslava', 'Drahuše', 'Dulcinea', 'Dušana', 'Edita', 'Eduarda', 'Egona', 'Ela',
        'Elektra', 'Eleonora', 'Elfrída', 'Eliška', 'Elvíra', 'Elza', 'Ema', 'Emanuela', 'Emílie', 'Erika', 'Erna',
        'Ervína', 'Ester', 'Etela', 'Eufrozína', 'Eulálie', 'Eunika', 'Eusebie', 'Eva', 'Evelína', 'Evženie', 'Fabie',
        'Fatima', 'Faustýna', 'Féba', 'Fedora', 'Felicita', 'Ferdinanda', 'Fidelie', 'Filipa', 'Filoména', 'Flavie',
        'Flóra', 'Florentýna', 'Františka', 'Frída', 'Gabriela', 'Gaja', 'Galina', 'Garika', 'Gema', 'Geralda', 'Gerarda',
        'Gerda', 'Gertruda', 'Gilberta', 'Gina', 'Gita', 'Gizela', 'Glorie', 'Gordana', 'Grácie', 'Gražina', 'Gréta',
        'Grizelda', 'Gudrun', 'Gustava', 'Gvendolína', 'Halina', 'Hana', 'Háta', 'Havla', 'Heda', 'Hedvika', 'Heidrun',
        'Helena', 'Helga', 'Herberta', 'Hermína', 'Herta', 'Hilda', 'Hortenzie', 'Horymíra', 'Hostimila', 'Hostislava',
        'Hvězdoslava', 'Hyacinta', 'Chranislava', 'Iboja', 'Ida', 'Ignácie', 'Ildika', 'Iljana', 'Ilona', 'Ilza', 'Inéz',
        'Ingeborg', 'Ingrid', 'Inka', 'Irena', 'Iris', 'Irma', 'Iva', 'Ivana', 'Iveta', 'Ivona', 'Izabela', 'Izidora',
        'Izolda', 'Jadrana', 'Jakubka', 'Jana', 'Jarmila', 'Jarolíma', 'Jaromíra', 'Jaroslava', 'Jasmína', 'Jasna',
        'Jelena', 'Jenovéfa', 'Jesika', 'Jindra', 'Jindřiška', 'Jiřina', 'Jitka', 'Jolana', 'Jolanta', 'Jordana', 'Jorga',
        'Josefa', 'Jovana', 'Judita', 'Juliána', 'Julie', 'Justýna', 'Juta', 'Kamila', 'Karin', 'Karla', 'Karmela',
        'Karmen', 'Karolína', 'Kateřina', 'Katrin', 'Kazi', 'Kazimíra', 'Kira', 'Klára', 'Klaudie', 'Klementýna',
        'Kleopatra', 'Klotylda', 'Koleta', 'Kolombína', 'Konstance', 'Konzuela', 'Kora', 'Kordula', 'Kornélie',
        'Krasava', 'Krasomila', 'Kristýna', 'Kunhuta', 'Květa', 'Květoslava', 'Lada', 'Ladislava', 'Larisa', 'Laura',
        'Laurencie', 'Lea', 'Léda', 'Lejla', 'Lenka', 'Leokádie', 'Leona', 'Leontýna', 'Leopolda', 'Leticie', 'Liběna',
        'Libora', 'Liboslava', 'Libuše', 'Liliana', 'Lina', 'Linda', 'Livie', 'Ljuba', 'Lola', 'Loreta', 'Lorna', 'Lota',
        'Lubomíra', 'Lucie', 'Ludiše', 'Luďka', 'Ludmila', 'Ludomíra', 'Ludoslava', 'Ludvíka', 'Lujza', 'Lukrécie',
        'Lumíra', 'Lýdie', 'Mabel', 'Magda', 'Magdaléna', 'Mahulena', 'Mája', 'Malvína', 'Manon', 'Manuela', 'Marcela',
        'Margit', 'Mariana', 'Marie', 'Marieta', 'Marika', 'Marilyn', 'Marina', 'Mariola', 'Marion', 'Marisa', 'Marita',
        'Markéta', 'Marlena', 'Marta', 'Martina', 'Matylda', 'Maud', 'Maxima', 'Mečislava', 'Médea', 'Melánie', 'Melinda',
        'Melisa', 'Mercedes', 'Michaela', 'Milada', 'Milana', 'Milena', 'Miloslava', 'Milred', 'Miluše', 'Mína',
        'Mirabela', 'Miranda', 'Mirela', 'Miriam', 'Mirka', 'Miromila', 'Miroslava', 'Mnislava', 'Mona', 'Monika',
        'Muriel', 'Myrna', 'Naďa', 'Naděžda', 'Naneta', 'Narcisa', 'Natálie', 'Nataša', 'Neda', 'Nela', 'Nevena',
        'Nika', 'Nikodéma', 'Nikol', 'Nila', 'Nina', 'Noema', 'Nona', 'Nora', 'Norberta', 'Norma', 'Odeta', 'Ofélie',
        'Oktávie', 'Oldřiška', 'Olga', 'Olivie', 'Olympie', 'Ondřejka', 'Otakara', 'Otýlie', 'Oxana', 'Palmira', 'Pamela',
        'Paskala', 'Patricie', 'Pavla', 'Pelagie', 'Penelopa', 'Perla', 'Perzida', 'Petra', 'Petronela', 'Petula',
        'Pilar', 'Polyxena', 'Pravomila', 'Pravoslav', 'Pravoslava', 'Priska', 'Prokopa', 'Přibyslava', 'Radana', 'Radka',
        'Radmila', 'Radomíra', 'Radoslava', 'Rafaela', 'Ráchel', 'Rajsa', 'Ramona', 'Rebeka', 'Regína', 'Renáta', 'René',
        'Ria', 'Richarda', 'Rina', 'Rita', 'Roberta', 'Robina', 'Romana', 'Rostislava', 'Rovena', 'Roxana', 'Róza',
        'Rozálie', 'Rozalinda', 'Rozamunda', 'Rozana', 'Rozina', 'Rozita', 'Rozvita', 'Rudolfa', 'Rut', 'Rút', 'Růžena',
        'Řehořka', 'Sabina', 'Sabrina', 'Salomea', 'Samuela', 'Sandra', 'Sára', 'Saskie', 'Saxona', 'Selena', 'Selma',
        'Senta', 'Serafína', 'Serena', 'Scholastika', 'Sibyla', 'Sidonie', 'Silvána', 'Silvie', 'Simona', 'Skarlet',
        'Slavěna', 'Slávka', 'Slavomila', 'Slavomíra', 'Soběslava', 'Sofronie', 'Solveig', 'Soňa', 'Sotira', 'Stanislava',
        'Stáza', 'Stela', 'Svatava', 'Svatoslava', 'Světla', 'Sylva', 'Šárka', 'Šarlota', 'Šimona', 'Štěpánka', 'Tamara',
        'Táňa', 'Taťána', 'Tea', 'Tekla', 'Teodora', 'Teodozie', 'Teofila', 'Tereza', 'Tomáška', 'Toska', 'Ulrika', 'Una',
        'Uršula', 'Václava', 'Valburga', 'Valdemara', 'Valentýna', 'Valérie', 'Vanda', 'Vanesa', 'Věduna', 'Veleslava',
        'Věnceslava', 'Vendelína', 'Vendula', 'Venuše', 'Věra', 'Veronika', 'Věroslava', 'Vesna', 'Viktorie', 'Vilma',
        'Vincencie', 'Viola', 'Virgínie', 'Víta', 'Vítězslava', 'Viviana', 'Vladana', 'Vladimíra', 'Vladislava', 'Vlasta',
        'Vlastimila', 'Vlastimíra', 'Vlastislava', 'Vojmíra', 'Vojslava', 'Vojtěška', 'Voršila', 'Vratislava', 'Xaverie',
        'Xenie', 'Zaida', 'Zaira', 'Zbyhněva', 'Zbyslava', 'Zdeňka', 'Zdislava', 'Zenobie', 'Zina', 'Zita', 'Zlata',
        'Zlatomíra', 'Zoe', 'Zora', 'Zoroslava', 'Zuzana', 'Zvonimíra', 'Žakelína', 'Žaneta', 'Ždana', 'Želimíra',
        'Želislava', 'Žitomíra', 'Živa', 'Žofie'];

    public function __construct(ApplicationsModel $applicationsModel, GendersModel $gendersModel)
    {

       parent::__construct();
        $this->applicationsModel = $applicationsModel;
        $this->genderModel = $gendersModel;
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/grid.latte');

        $this->template->render();
    }

    public function createComponentGrid(): Datagrid
    {
        $grid = parent::createComponentGrid();
        $grid->setPrimaryKey('id');
        $grid->setDefaultPerPage(50);
        $grid->setDataSource($this->applicationsModel->getApplicationsConfirmParticipantsNullGender());

        $grid->addColumnNumber('id', 'ID')
            ->setSortable();

        $grid->addColumnText('firstname', 'Křestní jméno')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('lastname', 'Příjmení')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('status', 'Status')
            ->setSortable()
            ->setFilterSelect(['Potvrzený','Waiting for action','Guest','Zájemce','Prihlásený','Smazaný'])
            ->setPrompt('');

        $grid->setColumnsHideable();
        $grid->addAction('edit', '', 'Applications:edit', ['id'])->setIcon('edit');

        return $grid;
    }

    public static function StartGenderScript($app): array
    {


            $Male = in_array($app['firstname'],self::NAMES_MALE);
            $Female = in_array($app['firstname'],self::NAMES_FEMALE);
            if($Male and $Female)
            {

            }
            else if($Male)
            {
               return array(2,$app['id']);
            }
           else if($Female)
            {
                return array(1,$app['id']);
            }

           return array(0);

    }
}