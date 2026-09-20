<?php
/**
 * Ciudades de ejemplo para el popup, por país.
 *
 * El popup en modo simulado necesita una ciudad que suene creíble. Hasta la
 * 1.4.0 la lista eran 128 municipios de Colombia para todo el mundo, y en la
 * 1.5.0 pasó a ser una cadena traducible, con lo que la elegía el idioma del
 * sitio: una tienda española acababa enseñando ciudades colombianas y una
 * portuguesa, estadounidenses. El idioma no dice dónde vende una tienda.
 *
 * Ahora la lista la elige el **país de la tienda** que ya está configurado en
 * WooCommerce (Ajustes → General → Ubicación de la tienda). Si ese país no
 * tiene lista aquí, el campo nace vacío y el popup sale sin ciudad hasta que el
 * dueño escriba las suyas: mejor eso que enseñar ciudades de otro continente.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * País de la tienda, en dos letras. Cadena vacía si no se puede saber.
 */
function dsb_store_country() {
    if ( function_exists( 'wc_get_base_location' ) ) {
        $base = wc_get_base_location();
        if ( ! empty( $base['country'] ) ) return (string) $base['country'];
    }

    // Respaldo por si WooCommerce aún no ha cargado: la opción guarda "CO:DC".
    $raw = (string) get_option( 'woocommerce_default_country', '' );
    if ( $raw ) {
        $parts = explode( ':', $raw );
        return (string) $parts[0];
    }

    return '';
}

/**
 * Listas por país. Las ciudades no se traducen: son nombres propios, y la
 * bandera se añade una vez por país en vez de repetirla en cada línea.
 */
function dsb_city_lists() {
    return [
        'CO' => [ 'flag' => '🇨🇴', 'cities' => [
            'Bogotá, D.C.', 'Medellín, Antioquia', 'Cali, Valle del Cauca', 'Barranquilla, Atlántico',
            'Cartagena, Bolívar', 'Cúcuta, Norte de Santander', 'Bucaramanga, Santander', 'Pereira, Risaralda',
            'Manizales, Caldas', 'Santa Marta, Magdalena', 'Ibagué, Tolima', 'Pasto, Nariño',
            'Montería, Córdoba', 'Armenia, Quindío', 'Villavicencio, Meta', 'Neiva, Huila',
            'Popayán, Cauca', 'Valledupar, Cesar', 'Sincelejo, Sucre', 'Tunja, Boyacá',
            'Florencia, Caquetá', 'Quibdó, Chocó', 'Riohacha, La Guajira', 'Mocoa, Putumayo',
            'Leticia, Amazonas', 'San Andrés, San Andrés y Providencia', 'Yopal, Casanare', 'Arauca, Arauca',
            'Envigado, Antioquia', 'Bello, Antioquia', 'Itagüí, Antioquia', 'Soledad, Atlántico',
            'Soacha, Cundinamarca', 'Palmira, Valle del Cauca', 'Buenaventura, Valle del Cauca', 'Floridablanca, Santander',
            'Girón, Santander', 'Dosquebradas, Risaralda', 'Tuluá, Valle del Cauca', 'Barrancabermeja, Santander',
            'Duitama, Boyacá', 'Sogamoso, Boyacá', 'Cartago, Valle del Cauca', 'Buga, Valle del Cauca',
            'Jamundí, Valle del Cauca', 'Yumbo, Valle del Cauca', 'Rionegro, Antioquia', 'Apartadó, Antioquia',
            'Sabaneta, Antioquia', 'Copacabana, Antioquia', 'La Estrella, Antioquia', 'Zipaquirá, Cundinamarca',
            'Chía, Cundinamarca', 'Facatativá, Cundinamarca', 'Fusagasugá, Cundinamarca', 'Mosquera, Cundinamarca',
            'Madrid, Cundinamarca', 'Funza, Cundinamarca', 'Cajicá, Cundinamarca', 'Girardot, Cundinamarca',
            'Magangué, Bolívar', 'Turbaco, Bolívar', 'Ciénaga, Magdalena', 'Ipiales, Nariño',
            'Tumaco, Nariño', 'Ocaña, Norte de Santander', 'Piedecuesta, Santander', 'San Gil, Santander',
            'Chiquinquirá, Boyacá', 'El Espinal, Tolima', 'Melgar, Tolima', 'Pitalito, Huila',
            'Garzón, Huila', 'Acacías, Meta', 'Granada, Meta', 'Maicao, La Guajira',
            'Aguachica, Cesar', 'Corozal, Sucre', 'Santa Rosa de Cabal, Risaralda', 'Chinchiná, Caldas',
            'La Dorada, Caldas', 'Calarcá, Quindío', 'Montenegro, Quindío', 'Cereté, Córdoba',
            'Sahagún, Córdoba', 'Lorica, Córdoba', 'Santander de Quilichao, Cauca', 'Puerto Tejada, Cauca',
        ] ],
        'US' => [ 'flag' => '🇺🇸', 'cities' => [
            'New York, NY', 'Los Angeles, CA', 'Chicago, IL', 'Houston, TX', 'Phoenix, AZ',
            'Philadelphia, PA', 'San Antonio, TX', 'San Diego, CA', 'Dallas, TX', 'Jacksonville, FL',
            'Austin, TX', 'Fort Worth, TX', 'San Jose, CA', 'Columbus, OH', 'Charlotte, NC',
            'Indianapolis, IN', 'San Francisco, CA', 'Seattle, WA', 'Denver, CO', 'Oklahoma City, OK',
            'Nashville, TN', 'Washington, DC', 'El Paso, TX', 'Las Vegas, NV', 'Boston, MA',
            'Detroit, MI', 'Portland, OR', 'Louisville, KY', 'Memphis, TN', 'Baltimore, MD',
            'Milwaukee, WI', 'Albuquerque, NM', 'Tucson, AZ', 'Fresno, CA', 'Sacramento, CA',
            'Mesa, AZ', 'Atlanta, GA', 'Kansas City, MO', 'Colorado Springs, CO', 'Omaha, NE',
            'Raleigh, NC', 'Miami, FL', 'Virginia Beach, VA', 'Long Beach, CA', 'Oakland, CA',
            'Minneapolis, MN', 'Bakersfield, CA', 'Tulsa, OK', 'Tampa, FL', 'Arlington, TX',
            'New Orleans, LA', 'Wichita, KS', 'Cleveland, OH', 'Orlando, FL', 'St. Louis, MO',
            'Pittsburgh, PA', 'Cincinnati, OH', 'Salt Lake City, UT', 'Boise, ID', 'Richmond, VA',
            'West Palm Beach, FL', 'Fort Lauderdale, FL', 'Charleston, SC', 'Savannah, GA', 'Austin, TX',
        ] ],
        'MX' => [ 'flag' => '🇲🇽', 'cities' => [
            'Ciudad de México', 'Guadalajara, Jalisco', 'Monterrey, Nuevo León', 'Puebla, Puebla',
            'Tijuana, Baja California', 'León, Guanajuato', 'Ciudad Juárez, Chihuahua', 'Zapopan, Jalisco',
            'Mérida, Yucatán', 'Cancún, Quintana Roo', 'Querétaro, Querétaro', 'San Luis Potosí, S.L.P.',
            'Aguascalientes, Aguascalientes', 'Culiacán, Sinaloa', 'Hermosillo, Sonora', 'Saltillo, Coahuila',
            'Morelia, Michoacán', 'Toluca, Estado de México', 'Chihuahua, Chihuahua', 'Veracruz, Veracruz',
            'Tuxtla Gutiérrez, Chiapas', 'Oaxaca, Oaxaca', 'Villahermosa, Tabasco', 'Durango, Durango',
            'Torreón, Coahuila', 'Acapulco, Guerrero', 'Puerto Vallarta, Jalisco', 'Playa del Carmen, Quintana Roo',
            'Pachuca, Hidalgo', 'Tampico, Tamaulipas', 'Mexicali, Baja California', 'Cuernavaca, Morelos',
            'Tepic, Nayarit', 'Colima, Colima', 'Campeche, Campeche', 'La Paz, Baja California Sur',
        ] ],
        'ES' => [ 'flag' => '🇪🇸', 'cities' => [
            'Madrid', 'Barcelona', 'Valencia', 'Sevilla', 'Zaragoza', 'Málaga', 'Murcia',
            'Palma, Baleares', 'Las Palmas de Gran Canaria', 'Bilbao, Vizcaya', 'Alicante', 'Córdoba',
            'Valladolid', 'Vigo, Pontevedra', 'Gijón, Asturias', 'Granada', 'A Coruña',
            'Santa Cruz de Tenerife', 'Pamplona, Navarra', 'Almería', 'San Sebastián, Guipúzcoa',
            'Santander, Cantabria', 'Castellón', 'Burgos', 'Albacete', 'Salamanca', 'Logroño, La Rioja',
            'Badajoz', 'Huelva', 'Tarragona', 'Lleida', 'León', 'Cádiz', 'Jaén', 'Ourense', 'Girona',
            'Toledo', 'Cáceres', 'Marbella, Málaga', 'Sitges, Barcelona',
        ] ],
        'AR' => [ 'flag' => '🇦🇷', 'cities' => [
            'Buenos Aires', 'Córdoba', 'Rosario, Santa Fe', 'Mendoza', 'La Plata, Buenos Aires',
            'San Miguel de Tucumán', 'Mar del Plata, Buenos Aires', 'Salta', 'Santa Fe', 'San Juan',
            'Resistencia, Chaco', 'Neuquén', 'Santiago del Estero', 'Corrientes', 'Posadas, Misiones',
            'Bahía Blanca, Buenos Aires', 'Paraná, Entre Ríos', 'Formosa', 'San Salvador de Jujuy',
            'Río Cuarto, Córdoba', 'San Carlos de Bariloche, Río Negro', 'Ushuaia, Tierra del Fuego',
            'Comodoro Rivadavia, Chubut', 'San Luis', 'San Fernando del Valle de Catamarca', 'La Rioja',
            'Villa María, Córdoba', 'Tandil, Buenos Aires',
        ] ],
        'CL' => [ 'flag' => '🇨🇱', 'cities' => [
            'Santiago', 'Puente Alto, Santiago', 'Antofagasta', 'Viña del Mar, Valparaíso', 'Valparaíso',
            'Talcahuano, Biobío', 'San Bernardo, Santiago', 'Temuco, Araucanía', 'Iquique, Tarapacá',
            'Concepción, Biobío', 'Rancagua, O\'Higgins', 'Talca, Maule', 'Arica', 'Coquimbo',
            'La Serena, Coquimbo', 'Puerto Montt, Los Lagos', 'Chillán, Ñuble', 'Calama, Antofagasta',
            'Osorno, Los Lagos', 'Valdivia, Los Ríos', 'Punta Arenas, Magallanes', 'Curicó, Maule',
            'Quilpué, Valparaíso', 'Copiapó, Atacama',
        ] ],
        'PE' => [ 'flag' => '🇵🇪', 'cities' => [
            'Lima', 'Arequipa', 'Trujillo, La Libertad', 'Chiclayo, Lambayeque', 'Piura',
            'Iquitos, Loreto', 'Cusco', 'Chimbote, Áncash', 'Huancayo, Junín', 'Tacna',
            'Juliaca, Puno', 'Ica', 'Sullana, Piura', 'Ayacucho', 'Cajamarca', 'Pucallpa, Ucayali',
            'Huánuco', 'Tarapoto, San Martín', 'Puno', 'Tumbes', 'Talara, Piura', 'Huaraz, Áncash',
            'Moquegua', 'Abancay, Apurímac',
        ] ],
        'BR' => [ 'flag' => '🇧🇷', 'cities' => [
            'São Paulo, SP', 'Rio de Janeiro, RJ', 'Brasília, DF', 'Salvador, BA', 'Fortaleza, CE',
            'Belo Horizonte, MG', 'Manaus, AM', 'Curitiba, PR', 'Recife, PE', 'Goiânia, GO',
            'Belém, PA', 'Porto Alegre, RS', 'Guarulhos, SP', 'Campinas, SP', 'São Luís, MA',
            'Maceió, AL', 'Natal, RN', 'Campo Grande, MS', 'Teresina, PI', 'João Pessoa, PB',
            'Florianópolis, SC', 'Vitória, ES', 'Cuiabá, MT', 'Aracaju, SE', 'Londrina, PR',
            'Niterói, RJ', 'Santos, SP', 'Ribeirão Preto, SP',
        ] ],
        'CA' => [ 'flag' => '🇨🇦', 'cities' => [
            'Toronto, ON', 'Montréal, QC', 'Vancouver, BC', 'Calgary, AB', 'Edmonton, AB',
            'Ottawa, ON', 'Winnipeg, MB', 'Québec, QC', 'Hamilton, ON', 'Kitchener, ON',
            'London, ON', 'Victoria, BC', 'Halifax, NS', 'Oshawa, ON', 'Windsor, ON',
            'Saskatoon, SK', 'Regina, SK', 'St. John\'s, NL', 'Kelowna, BC', 'Barrie, ON',
            'Guelph, ON', 'Sherbrooke, QC', 'Moncton, NB', 'Burnaby, BC',
        ] ],
        'GB' => [ 'flag' => '🇬🇧', 'cities' => [
            'London', 'Birmingham', 'Manchester', 'Glasgow', 'Liverpool', 'Leeds', 'Sheffield',
            'Edinburgh', 'Bristol', 'Cardiff', 'Leicester', 'Coventry', 'Nottingham',
            'Newcastle upon Tyne', 'Belfast', 'Brighton', 'Southampton', 'Portsmouth', 'Oxford',
            'Cambridge', 'York', 'Aberdeen', 'Norwich', 'Bath',
        ] ],
    ];
}

/**
 * Ciudades por defecto de esta tienda. Array vacío si su país no tiene lista:
 * entonces el popup se muestra sin ciudad y el panel lo avisa.
 */
function dsb_default_locations() {
    $lists   = dsb_city_lists();
    $country = dsb_store_country();

    if ( ! isset( $lists[ $country ] ) ) return [];

    $flag = $lists[ $country ]['flag'];

    return array_map( function ( $city ) use ( $flag ) {
        return $city . ' ' . $flag;
    }, $lists[ $country ]['cities'] );
}
