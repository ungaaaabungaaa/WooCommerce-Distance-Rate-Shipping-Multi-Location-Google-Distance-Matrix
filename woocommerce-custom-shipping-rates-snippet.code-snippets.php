<?php

/**
 * WooCommerce Custom Shipping Rates Snippet
 *
 * * Constructor: Initializes properties such as id, title, and instance_form_fields.
 * calculate_shipping Method: Determines shipping cost based on the distance from the nearest store. It retrieves destination address, calculates distance from each store, selects the nearest store, and calculates shipping cost using the calculate_shipping_cost method.
 * get_distance Method: Calculates distance between two coordinates using the Haversine formula.
 * calculate_shipping_cost Method: Computes shipping cost based on the distance from the nearest store. It hides the shipping method if the distance is less than 5 km. For distances beyond 5 km, it charges $15 for each extra kilometer beyond the initial 5 km, up to a maximum of 10 km.
 *
 */
// Custom shipping method class
class Custom_Shipping_Method extends WC_Shipping_Method
{
    public function __construct($instance_id = 0)
    {
        $this->id = 'custom_shipping';
        $this->title = __('Custom Shipping', 'woocommerce'); // Set the title for the shipping method
        $this->instance_form_fields = array(
            'title' => array(
                'title' => __('Title', 'woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                'default' => __('Home Delivery Charges', 'woocommerce'),
                'desc_tip' => true,
            ),
        );
        $this->init();
    }

    /**
     * Calculate the shipping cost based on the distance from the nearest store.
     *
     * @param array $package The package details.
     * @return void
     */
    public function calculate_shipping($package = array())
    {
        // Get the user's address
        $address = array(
            'address' => $package['destination']['address_1'],
            'city' => $package['destination']['city'],
            'state' => $package['destination']['state'],
            'country' => $package['destination']['country'],
            'postcode' => $package['destination']['postcode'],
        );

        // Store locations (replace with your actual store locations)
        $stores = [
            [
                "name" => "Jayanagar",
                "longitude" => "77.5816819",
                "latitude" => "12.9292656",
                "status" => "Enabled"
            ],
            // Add more store data here
        ];

        // Initialize the nearest store distance
        $nearest_distance = PHP_INT_MAX;
        $nearest_store = null;

        // Find the nearest store
        foreach ($stores as $store) {
            $distance = $this->get_distance($store['latitude'], $store['longitude'], $address['latitude'], $address['longitude']);

            if ($distance < $nearest_distance) {
                $nearest_distance = $distance;
                $nearest_store = $store;
            }
        }

        // Calculate the shipping rate based on the distance from the nearest store
        $shipping_cost = $this->calculate_shipping_cost($nearest_distance);

        // Add shipping rate
        $rate = array(
            'id' => $this->id,
            'label' => $this->title,
            'cost' => $shipping_cost,
            'package' => $package,
        );
        $this->add_rate($rate);
    }

    /**
     * Calculate the distance between two coordinates.
     *
     * @param float $lat1 Latitude of point 1.
     * @param float $lon1 Longitude of point 1.
     * @param float $lat2 Latitude of point 2.
     * @param float $lon2 Longitude of point 2.
     * @return float Distance between the points in meters.
     */

    private function get_distance($lat1, $lon1, $lat2, $lon2)
    {
        // Use Haversine formula to calculate distance
        $earth_radius = 6371000; // Earth's radius in meters
        $delta_lat = deg2rad($lat2 - $lat1);
        $delta_lon = deg2rad($lon2 - $lon1);
        $a = sin($delta_lat / 2) * sin($delta_lat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($delta_lon / 2) * sin($delta_lon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earth_radius * $c;

        return $distance;
    }

    /**
     * Calculate shipping cost based on the distance.
     *
     * @param float $distance Distance from the nearest store in meters.
     * @return float Shipping cost.
     */

    private function calculate_shipping_cost($distance)
    {
        // Hide the shipping method if distance is less than 5 km
		if ($distance < 5000) {
			return false; // Return false to hide the shipping method
		} else {
			// Calculate the shipping cost for distances beyond 5 km
			$distance_km = ceil($distance / 1000);
			$extra_km = $distance_km - 5; // Calculate extra kilometers beyond the initial 5 km
			// Cap the extra kilometers at 5 km (maximum chargeable distance beyond 5 km)
			$extra_km = min($extra_km, 5);
			// Calculate the shipping cost
			$cost = $extra_km * 15; // Charge $15 for each extra kilometer
			return $cost;
		}
    }
}

// Register the custom shipping method
function add_custom_shipping_method($methods)
{
    $methods[] = 'Custom_Shipping_Method';
    return $methods;
}
add_filter('woocommerce_shipping_methods', 'add_custom_shipping_method');
