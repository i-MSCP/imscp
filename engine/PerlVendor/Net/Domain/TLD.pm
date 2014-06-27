package Net::Domain::TLD;
use strict;
use base qw(Exporter);
use 5.006;
our @EXPORT_OK = qw(tlds tld_exists);
our $VERSION = '1.72';

use warnings;
use Carp;
use Storable qw ( dclone );

use constant TLD_TYPES => qw ( new_open new_restricted gtld_open gtld_restricted gtld_new cc ccidn );

=head1 NAME

  Net::Domain::TLD - Work with TLD names

=head1 SYNOPSIS

  use Net::Domain::TLD qw(tlds tld_exists);
  my @ccTLDs = tlds('cc');
  print "TLD ok\n" if tld_exists('ac','cc');

=head1 DESCRIPTION

  The purpose of this module is to provide user with current list of
  available top level domain names including new ICANN additions and ccTLDs
  Currently TLD definitions have been acquired from the following sources:

  http://www.icann.org/tlds/
  http://www.dnso.org/constituency/gtld/gtld.html
  http://www.iana.org/cctld/cctld-whois.htm
  https://www.iana.org/domains/root/db

=cut

my %tld_profile = (
  reserved => {
    test => q{DNS testing names},
    example => q{Documentation names},
    invalid => q{Invalid names},
    localhost => q{Loopback names}
  },
  new_open => {
    info => q{Unrestricted use},
    xxx => q{sponsored top-level domain}
  },
  new_restricted => {
    aero => q{Air-transport industry},
    asia => q{Companies, organisations and individuals in the Asia-Pacific region},
    arpa => q{Address and Routing Parameter Area},
    biz => q{Businesses},
    cat => q{Catalan linguistic and cultural community},
    coop => q{Cooperatives},
    jobs => q{Human Resource Management},
    mobi => q{Mobile},
    museum => q{Museums},
    name => q{For registration by individuals},
    post => q{Universal Postal Union},
    pro => q{Accountants, lawyers, and physicians},
    travel => q{Travel industry},
    tel => q{For businesses and individuals to publish contact data}
  },
  gtld_open => {
    com => q{Commercial organization},
    net => q{Network connection services provider},
    org => q{Non-profit organizations and industry standard groups}
  },
  gtld_restricted => {
    gov => q{United States Government},
    mil => q{United States Military},
    edu => q{Educational institution},
    int => q{International treaties/databases},
  },
  cc => {
    ac => q{Ascension Island},
    ad => q{Andorra},
    ae => q{United Arab Emirates},
    af => q{Afghanistan},
    ag => q{Antigua and Barbuda},
    ai => q{Anguilla},
    al => q{Albania},
    am => q{Armenia},
    an => q{Netherlands Antilles},
    ao => q{Angola},
    aq => q{Antartica},
    ar => q{Argentina},
    as => q{American Samoa},
    at => q{Austria},
    au => q{Australia},
    aw => q{Aruba},
    ax => q(Aland Islands),
    az => q{Azerbaijan},
    ba => q{Bosnia and Herzegovina},
    bb => q{Barbados},
    bd => q{Bangladesh},
    be => q{Belgium},
    bf => q{Burkina Faso},
    bg => q{Bulgaria},
    bh => q{Bahrain},
    bi => q{Burundi},
    bj => q{Benin},
    bl => q(Saint Barthelemy),
    bm => q{Bermuda},
    bn => q{Brunei Darussalam},
    bo => q{Bolivia},
    bq => q{Not assigned},
    br => q{Brazil},
    bs => q{Bahamas},
    bt => q{Bhutan},
    bv => q{Bouvet Island},
    bw => q{Botswana},
    by => q{Belarus},
    bz => q{Belize},
    ca => q{Canada},
    cc => q{Cocos (Keeling) Islands},
    cd => q{Congo, Democratic Republic of the},
    cf => q{Central African Republic},
    cg => q{Congo, Republic of},
    ch => q{Switzerland},
    ci => q{Cote d'Ivoire},
    ck => q{Cook Islands},
    cl => q{Chile},
    cm => q{Cameroon},
    cn => q{China},
    co => q{Colombia},
    cr => q{Costa Rica},
    cu => q{Cuba},
    cv => q{Cap Verde},
    cw => q{University of the Netherlands Antilles},
    cx => q{Christmas Island},
    cy => q{Cyprus},
    cz => q{Czech Republic},
    de => q{Germany},
    dj => q{Djibouti},
    dk => q{Denmark},
    dm => q{Dominica},
    do => q{Dominican Republic},
    dz => q{Algeria},
    ec => q{Ecuador},
    ee => q{Estonia},
    eg => q{Egypt},
    eh => q{Western Sahara},
    er => q{Eritrea},
    es => q{Spain},
    et => q{Ethiopia},
    eu => q{European Union},
    fi => q{Finland},
    fj => q{Fiji},
    fk => q{Falkland Islands (Malvina)},
    fm => q{Micronesia, Federal State of},
    fo => q{Faroe Islands},
    fr => q{France},
    ga => q{Gabon},
    gb => q{United Kingdom},
    gd => q{Grenada},
    ge => q{Georgia},
    gf => q{French Guiana},
    gg => q{Guernsey},
    gh => q{Ghana},
    gi => q{Gibraltar},
    gl => q{Greenland},
    gm => q{Gambia},
    gn => q{Guinea},
    gp => q{Guadeloupe},
    gq => q{Equatorial Guinea},
    gr => q{Greece},
    gs => q{South Georgia and the South Sandwich Islands},
    gt => q{Guatemala},
    gu => q{Guam},
    gw => q{Guinea-Bissau},
    gy => q{Guyana},
    hk => q{Hong Kong},
    hm => q{Heard and McDonald Islands},
    hn => q{Honduras},
    hr => q{Croatia/Hrvatska},
    ht => q{Haiti},
    hu => q{Hungary},
    id => q{Indonesia},
    ie => q{Ireland},
    il => q{Israel},
    im => q{Isle of Man},
    in => q{India},
    io => q{British Indian Ocean Territory},
    iq => q{Iraq},
    ir => q{Iran (Islamic Republic of)},
    is => q{Iceland},
    it => q{Italy},
    je => q{Jersey},
    jm => q{Jamaica},
    jo => q{Jordan},
    jp => q{Japan},
    ke => q{Kenya},
    kg => q{Kyrgyzstan},
    kh => q{Cambodia},
    ki => q{Kiribati},
    km => q{Comoros},
    kn => q{Saint Kitts and Nevis},
    kp => q{Korea, Democratic People's Republic},
    kr => q{Korea, Republic of},
    kw => q{Kuwait},
    ky => q{Cayman Islands},
    kz => q{Kazakhstan},
    la => q{Lao People's Democratic Republic},
    lb => q{Lebanon},
    lc => q{Saint Lucia},
    li => q{Liechtenstein},
    lk => q{Sri Lanka},
    lr => q{Liberia},
    ls => q{Lesotho},
    lt => q{Lithuania},
    lu => q{Luxembourg},
    lv => q{Latvia},
    ly => q{Libyan Arab Jamahiriya},
    ma => q{Morocco},
    mc => q{Monaco},
    md => q{Moldova, Republic of},
    me => q(Montenegro),
    mf => q{Saint Martin (French part)},
    mg => q{Madagascar},
    mh => q{Marshall Islands},
    mk => q{Macedonia, Former Yugoslav Republic},
    ml => q{Mali},
    mm => q{Myanmar},
    mn => q{Mongolia},
    mo => q{Macau},
    mp => q{Northern Mariana Islands},
    mq => q{Martinique},
    mr => q{Mauritania},
    ms => q{Montserrat},
    mt => q{Malta},
    mu => q{Mauritius},
    mv => q{Maldives},
    mw => q{Malawi},
    mx => q{Mexico},
    my => q{Malaysia},
    mz => q{Mozambique},
    na => q{Namibia},
    nc => q{New Caledonia},
    ne => q{Niger},
    nf => q{Norfolk Island},
    ng => q{Nigeria},
    ni => q{Nicaragua},
    nl => q{Netherlands},
    no => q{Norway},
    np => q{Nepal},
    nr => q{Nauru},
    nu => q{Niue},
    nz => q{New Zealand},
    om => q{Oman},
    pa => q{Panama},
    pe => q{Peru},
    pf => q{French Polynesia},
    pg => q{Papua New Guinea},
    ph => q{Philippines},
    pk => q{Pakistan},
    pl => q{Poland},
    pm => q{St. Pierre and Miquelon},
    pn => q{Pitcairn Island},
    pr => q{Puerto Rico},
    ps => q{Palestinian Territories},
    pt => q{Portugal},
    pw => q{Palau},
    py => q{Paraguay},
    qa => q{Qatar},
    re => q{Reunion Island},
    ro => q{Romania},
    rs => q(Serbia),
    ru => q{Russian Federation},
    rw => q{Rwanda},
    sa => q{Saudi Arabia},
    sb => q{Solomon Islands},
    sc => q{Seychelles},
    sd => q{Sudan},
    se => q{Sweden},
    sg => q{Singapore},
    sh => q{St. Helena},
    si => q{Slovenia},
    sj => q{Svalbard and Jan Mayen Islands},
    sk => q{Slovak Republic},
    sl => q{Sierra Leone},
    sm => q{San Marino},
    sn => q{Senegal},
    so => q{Somalia},
    sr => q{Suriname},
    ss => q{Not assigned},
    st => q{Sao Tome and Principe},
    su => q{Soviet Union},
    sv => q{El Salvador},
    sx => q{SX Registry SA B.V.},
    sy => q{Syrian Arab Republic},
    sz => q{Swaziland},
    tc => q{Turks and Caicos Islands},
    td => q{Chad},
    tf => q{French Southern Territories},
    tg => q{Togo},
    th => q{Thailand},
    tj => q{Tajikistan},
    tk => q{Tokelau},
    tl => q{Timor-Leste},
    tm => q{Turkmenistan},
    tn => q{Tunisia},
    to => q{Tonga},
    tp => q{East Timor},
    tr => q{Turkey},
    tt => q{Trinidad and Tobago},
    tv => q{Tuvalu},
    tw => q{Taiwan},
    tz => q{Tanzania},
    ua => q{Ukraine},
    ug => q{Uganda},
    uk => q{United Kingdom},
    um => q{US Minor Outlying Islands},
    us => q{United States},
    uy => q{Uruguay},
    uz => q{Uzbekistan},
    va => q{Holy See (City Vatican State)},
    vc => q{Saint Vincent and the Grenadines},
    ve => q{Venezuela},
    vg => q{Virgin Islands (British)},
    vi => q{Virgin Islands (USA)},
    vn => q{Vietnam},
    vu => q{Vanuatu},
    wf => q{Wallis and Futuna Islands},
    ws => q{Western Samoa},
    ye => q{Yemen},
    yt => q{Mayotte},
    yu => q{Yugoslavia},
    za => q{South Africa},
    zm => q{Zambia},
    zw => q{Zimbabwe}
  },
  ccidn => {
    'xn--0zwm56d' => q{Internet Assigned Numbers Authority},
    'xn--11b5bs3a9aj6g' => q{Internet Assigned Numbers Authority},
    'xn--3e0b707e' => q{KISA (Korea Internet &amp; Security Agency)},
    'xn--45brj9c' => q{National Internet Exchange of India},
    'xn--54b7fta0cc' => q{Not assigned},
    'xn--80akhbyknj4f' => q{Internet Assigned Numbers Authority},
    'xn--80ao21a' => q{Association of IT Companies of Kazakhstan},
    'xn--90a3ac' => q{Serbian National Register of Internet Domain Names (RNIDS)},
    'xn--9t4b11yi5a' => q{Internet Assigned Numbers Authority},
    'xn--clchc0ea0b2g2a9gcd' => q{Singapore Network Information Centre (SGNIC) Pte Ltd},
    'xn--deba0ad' => q{Internet Assigned Numbers Authority},
    'xn--fiqs8s' => q{China Internet Network Information Center},
    'xn--fiqz9s' => q{China Internet Network Information Center},
    'xn--fpcrj9c3d' => q{National Internet Exchange of India},
    'xn--fzc2c9e2c' => q{LK Domain Registry},
    'xn--g6w251d' => q{Internet Assigned Numbers Authority},
    'xn--gecrj9c' => q{National Internet Exchange of India},
    'xn--h2brj9c' => q{National Internet Exchange of India},
    'xn--hgbk6aj7f53bba' => q{Internet Assigned Numbers Authority},
    'xn--hlcj6aya9esc7a' => q{Internet Assigned Numbers Authority},
    'xn--j1amh' => q{Ukrainian Network Information Centre (UANIC), Inc.},
    'xn--j6w193g' => q{Hong Kong Internet Registration Corporation Ltd.},
    'xn--jxalpdlp' => q{Internet Assigned Numbers Authority},
    'xn--kgbechtv' => q{Internet Assigned Numbers Authority},
    'xn--kprw13d' => q{Taiwan Network Information Center (TWNIC)},
    'xn--kpry57d' => q{Taiwan Network Information Center (TWNIC)},
    'xn--l1acc' => q{Datacom Co.,Ltd},
    'xn--lgbbat1ad8j' => q{CERIST},
    'xn--mgb9awbf' => q{Telecommunications Regulatory Authority (TRA)},
    'xn--mgba3a4f16a' => q{Not assigned},
    'xn--mgbaam7a8h' => q{Telecommunications Regulatory Authority (TRA)},
    'xn--mgbai9azgqp6j' => q{Not assigned},
    'xn--mgbayh7gpa' => q{National Information Technology Center (NITC)},
    'xn--mgbbh1a71e' => q{National Internet Exchange of India},
    'xn--mgbc0a9azcg' => q{Agence Nationale de Réglementation des Télécommunications (ANRT)},
    'xn--mgberp4a5d4ar' => q{Communications and Information Technology Commission},
    'xn--mgbpl2fh' => q{Not assigned},
    'xn--mgbx4cd0ab' => q{MYNIC Berhad},
    'xn--node' => q{Not assigned},
    'xn--o3cw4h' => q{Thai Network Information Center Foundation},
    'xn--ogbpf8fl' => q{National Agency for Network Services (NANS)},
    'xn--p1ai' => q{Coordination Center for TLD RU},
    'xn--pgbs0dh' => q{Agence Tunisienne d&#39;Internet},
    'xn--s9brj9c' => q{National Internet Exchange of India},
    'xn--wgbh1c' => q{National Telecommunication Regulatory Authority - NTRA},
    'xn--wgbl6a' => q{Supreme Council for Communications and Information Technology (ictQATAR)},
    'xn--xkc2al3hye2a' => q{LK Domain Registry},
    'xn--xkc2dl3a5ee0h' => q{National Internet Exchange of India},
    'xn--yfro4i67o' => q{Singapore Network Information Centre (SGNIC) Pte Ltd},
    'xn--ygbi2ammx' => q{Ministry of Telecom &amp; Information Technology (MTIT)},
    'xn--zckzah' => q{Internet Assigned Numbers Authority},
    'xn--3bst00m' => q{Eagle Horizon Limited},
    'xn--3ds443g' => q{TLD REGISTRY LIMITED},
    'xn--55qw42g' => q{China Organizational Name Administration Center},
    'xn--55qx5d' => q{Computer Network Information Center of Chinese Academy of Sciences （China Internet Network Information Center）},
    'xn--6frz82g' => q{Afilias Limited},
    'xn--6qq986b3xl' => q{Tycoon Treasure Limited},
    'xn--80asehdb' => q{CORE Association},
    'xn--80aswg' => q{CORE Association},
    'xn--c1avg' => q{Public Interest Registry},
    'xn--cg4bki' => q{SAMSUNG SDS CO., LTD},
    'xn--d1acj3b' => q{The Foundation for Network Initiatives “The Smart Internet”},
    'xn--fiq228c5hs' => q{TLD REGISTRY LIMITED},
    'xn--fiq64b' => q{CITIC Group Corporation},
    'xn--i1b6b1a6a2e' => q{Public Interest Registry},
    'xn--io0a7i' => q{Computer Network Information Center of Chinese Academy of Sciences （China Internet Network Information Center）},
    'xn--mgbab2bd' => q{CORE Association},
    'xn--ngbc5azd' => q{International Domain Registry Pty. Ltd.},
    'xn--nqv7f' => q{Public Interest Registry},
    'xn--nqv7fs00ema' => q{Public Interest Registry},
    'xn--q9jyb4c' => q{Charleston Road Registry Inc.},
    'xn--rhqv96g' => q{Stable Tone Limited},
    'xn--unup4y' => q{Spring Fields, LLC},
    'xn--zfr164b' => q{China Organizational Name Administration Center}
  },
  gtld_new => {
    'academy' => q{Half Oaks, LLC},
    'actor' => q{United TLD Holdco Ltd.},
    'agency' => q{Steel Falls, LLC},
    'axa' => q{AXA SA},
    'bar' => q{Punto 2012 Sociedad Anonima Promotora de Inversion de Capital Variable},
    'bargains' => q{Half Hallow, LLC},
    'berlin' => q{dotBERLIN GmbH &amp; Co. KG},
    'best' => q{BestTLD Pty Ltd},
    'bid' => q{dot Bid Limited},
    'bike' => q{Grand Hollow, LLC},
    'blue' => q{Afilias Limited},
    'boutique' => q{Over Galley, LLC},
    'build' => q{Plan Bee LLC},
    'builders' => q{Atomic Madison, LLC},
    'buzz' => q{DOTSTRATEGY CO.},
    'cab' => q{Half Sunset, LLC},
    'camera' => q{Atomic Maple, LLC},
    'camp' => q{Delta Dynamite, LLC},
    'cards' => q{Foggy Hollow, LLC},
    'careers' => q{Wild Corner, LLC},
    'catering' => q{New Falls. LLC},
    'center' => q{Tin Mill, LLC},
    'ceo' => q{CEOTLD Pty Ltd},
    'cheap' => q{Sand Cover, LLC},
    'christmas' => q{Uniregistry, Corp.},
    'cleaning' => q{Fox Shadow, LLC},
    'clothing' => q{Steel Lake, LLC},
    'club' => q{.CLUB DOMAINS, LLC},
    'codes' => q{Puff Willow, LLC},
    'coffee' => q{Trixy Cover, LLC},
    'cologne' => q{NetCologne Gesellschaft für Telekommunikation mbH},
    'community' => q{Fox Orchard, LLC},
    'company' => q{Silver Avenue, LLC},
    'computer' => q{Pine Mill, LLC},
    'construction' => q{Fox Dynamite, LLC},
    'contractors' => q{Magic Woods, LLC},
    'cool' => q{Koko Lake, LLC},
    'cruises' => q{Spring Way, LLC},
    'dance' => q{United TLD Holdco Ltd.},
    'dating' => q{Pine Fest, LLC},
    'democrat' => q{United TLD Holdco Ltd.},
    'diamonds' => q{John Edge, LLC},
    'directory' => q{Extra Madison, LLC},
    'domains' => q{Sugar Cross, LLC},
    'education' => q{Brice Way, LLC},
    'email' => q{Spring Madison, LLC},
    'enterprises' => q{Snow Oaks, LLC},
    'equipment' => q{Corn Station, LLC},
    'estate' => q{Trixy Park, LLC},
    'events' => q{Pioneer Maple, LLC},
    'expert' => q{Magic Pass, LLC},
    'exposed' => q{Victor Beach, LLC},
    'farm' => q{Just Maple, LLC},
    'fish' => q{Fox Woods, LLC},
    'flights' => q{Fox Station, LLC},
    'florist' => q{Half Cypress, LLC},
    'foundation' => q{John Dale, LLC},
    'futbol' => q{United TLD Holdco, Ltd.},
    'gallery' => q{Sugar House, LLC},
    'gift' => q{Uniregistry, Corp.},
    'glass' => q{Black Cover, LLC},
    'graphics' => q{Over Madison, LLC},
    'guitars' => q{Uniregistry, Corp.},
    'guru' => q{Pioneer Cypress, LLC},
    'holdings' => q{John Madison, LLC},
    'holiday' => q{Goose Woods, LLC},
    'house' => q{Sugar Park, LLC},
    'immobilien' => q{United TLD Holdco Ltd.},
    'industries' => q{Outer House, LLC},
    'institute' => q{Outer Maple, LLC},
    'international' => q{Wild Way, LLC},
    'jetzt' => q{New TLD Company AB},
    'kaufen' => q{United TLD Holdco Ltd.},
    'kim' => q{Afilias Limited},
    'kitchen' => q{Just Goodbye, LLC},
    'kiwi' => q{DOT KIWI LIMITED},
    'koeln' => q{NetCologne Gesellschaft für Telekommunikation mbH},
    'kred' => q{KredTLD Pty Ltd},
    'land' => q{Pine Moon, LLC},
    'lighting' => q{John McCook, LLC},
    'limo' => q{Hidden Frostbite, LLC},
    'link' => q{Uniregistry, Corp.},
    'luxury' => q{Luxury Partners LLC},
    'management' => q{John Goodbye, LLC},
    'mango' => q{PUNTO FA S.L.},
    'marketing' => q{Fern Pass, LLC},
    'menu' => q{Wedding TLD2, LLC},
    'moda' => q{United TLD Holdco Ltd.},
    'monash' => q{Monash University},
    'nagoya' => q{GMO Registry, Inc.},
    'neustar' => q{NeuStar, Inc.},
    'ninja' => q{United TLD Holdco Ltd.},
    'okinawa' => q{BusinessRalliart inc.},
    'onl' => q{I-REGISTRY Ltd., Niederlassung Deutschland},
    'partners' => q{Magic Glen, LLC},
    'parts' => q{Sea Goodbye, LLC},
    'photo' => q{Uniregistry, Corp.},
    'photography' => q{Sugar Glen, LLC},
    'photos' => q{Sea Corner, LLC},
    'pics' => q{Uniregistry, Corp.},
    'pink' => q{Afilias Limited},
    'plumbing' => q{Spring Tigers, LLC},
    'productions' => q{Magic Birch, LLC},
    'properties' => q{Big Pass, LLC},
    'pub' => q{United TLD Holdco Ltd.},
    'qpon' => q{dotCOOL, Inc.},
    'recipes' => q{Grand Island, LLC},
    'red' => q{Afilias Limited},
    'rentals' => q{Big Hollow,LLC},
    'repair' => q{Lone Sunset, LLC},
    'report' => q{Binky Glen, LLC},
    'reviews' => q{United TLD Holdco, Ltd.},
    'rich' => q{I-REGISTRY Ltd., Niederlassung Deutschland},
    'ruhr' => q{regiodot GmbH &amp; Co. KG},
    'sexy' => q{Uniregistry, Corp.},
    'shiksha' => q{Afilias Limited},
    'shoes' => q{Binky Galley, LLC},
    'singles' => q{Fern Madison, LLC},
    'social' => q{United TLD Holdco Ltd.},
    'solar' => q{Ruby Town, LLC},
    'solutions' => q{Silver Cover, LLC},
    'supplies' => q{Atomic Fields, LLC},
    'supply' => q{Half Falls, LLC},
    'support' => q{Grand Orchard, LLC},
    'systems' => q{Dash Cypress, LLC},
    'tattoo' => q{Uniregistry, Corp.},
    'technology' => q{Auburn Falls, LLC},
    'tienda' => q{Victor Manor, LLC},
    'tips' => q{Corn Willow, LLC},
    'today' => q{Pearl Woods, LLC},
    'tokyo' => q{GMO Registry, Inc.},
    'tools' => q{Pioneer North, LLC},
    'trade' => q{Elite Registry Limited},
    'training' => q{Wild Willow, LLC},
    'uno' => q{Dot Latin LLC},
    'vacations' => q{Atomic Tigers, LLC},
    'ventures' => q{Binky Lake, LLC},
    'viajes' => q{Black Madison, LLC},
    'villas' => q{New Sky, LLC},
    'vision' => q{Koko Station, LLC},
    'vote' => q{Monolith Registry LLC},
    'voting' => q{Valuetainment Corp.},
    'voto' => q{Monolith Registry LLC},
    'voyage' => q{Ruby House, LLC},
    'wang' => q{Zodiac Leo Limited},
    'watch' => q{Sand Shadow, LLC},
    'webcam' => q{dot Webcam Limited},
    'wed' => q{Atgron, Inc.},
    'wien' => q{punkt.wien GmbH},
    'wiki' => q{Top Level Design, LLC},
    'works' => q{Little Dynamite, LLC},
    'xyz' => q{XYZ.COM LLC},
    'zone' => q{Outer Falls, LLC}
  }
);

my $flat_profile = flatten ( \%tld_profile );

sub flatten {
  my $hashref = shift;
  my %results;
  @results{ keys %{ $hashref->{$_} } } = values % { $hashref->{$_} }
    for ( keys %$hashref );
  return \%results;
}

sub check_type {
  my $type = shift;
  croak "unknown TLD type: $type" unless grep { $type eq $_ } TLD_TYPES;
  return 1;
}

=head1 PUBLIC METHODS

  Each public function/method is described here.
  These are how you should interact with this module.

=head3 C<< tlds >>

  This routine returns the tlds requested.

  my @all_tlds = tlds; #array of tlds
  my $all_tlds = tlds; #hashref of tlds and their descriptions

  my @cc_tlds = tlds('cc'); #array of just 'cc' type tlds
  my $cc_tlds = tlds('cc'); #hashref of just 'cc' type tlds and their descriptions

  Valid types are:
    cc                 - country code domains
    ccidn              - internationalized country code top-level domain
    gtld_open          - generic domains that anyone can register
    gtld_restricted    - generic restricted registration domains
    gtld_new           - new gTLDs
    new_open           - recently added generic domains
    new_restricted     - new restricted registration domains
    reserved           - RFC2606 restricted names, not returned by tlds

=cut

sub tlds {
  my $type = shift;
  check_type ( $type ) if $type;
  my $results = $type ?
    wantarray ? [ keys %{ $tld_profile{$type} } ] :
      dclone ( $tld_profile{$type} ) :
	wantarray ? [ map { keys %$_ } values %tld_profile ] :
	  $flat_profile;
  return wantarray ? @$results : $results;
}

=head3 C<< tld_exists >>

  This routine returns true if the given domain exists and false otherwise.

  die "no such domain" unless tld_exists($tld); #call without tld type
  die "no such domain" unless tld_exists($tld, 'new_open'); #call with tld type

=cut

sub tld_exists {
  my ( $tld, $type )  = ( lc ( $_[0] ), $_[1] );
  check_type ( $type ) if $type;
  my $result = $type ?
    $tld_profile{$type}{$tld} ? 1 : 0 :
    $flat_profile->{$tld} ? 1 : 0;
  return $result;
}

=head1 COPYRIGHT

  Copyright (c) 2003-2014 Alex Pavlovic, all rights reserved.  This program
  is free software; you can redistribute it and/or modify it under the same terms
  as Perl itself.

=head1 AUTHORS

  Alexander Pavlovic <alex.pavlovic@taskforce-1.com>
  Ricardo SIGNES <rjbs@cpan.org>

=cut

1;
