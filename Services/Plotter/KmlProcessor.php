<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Plotter;

use Services\Exceptions\Plotter\InvalidPolygonException;
use SimpleXMLElement;

/**
 * Class KmlProcessor
 * The logic in this class has been moved across from plotter
 */
class KmlProcessor
{
    
    const PLACE_MARK_NAME_KEY = 'name';
    const PLACE_MARK_POLYGON_KEY = 'polygon';
    const PLACE_KEY = 'place';
    const PLACES_KEY = 'places';
    const HAS_INVALID_POLYGONS_KEY = 'has_invalid_polygons';

    /**
     * @param SimpleXMLElement $kml
     * @return array
     */
    public function process(SimpleXMLElement $kml)
    {
        $places = [];
        $hasInvalidPolygons = false;

        if (isset($kml->Document)) {
            $processed = $this->process($kml->Document);
            $hasInvalidPolygons = $processed[self::HAS_INVALID_POLYGONS_KEY];
            $places = array_merge($places, $processed[self::PLACES_KEY]);
        }
    
        if (isset($kml->Folder)) {
            foreach ($kml->Folder as $folder) {
                $processed = $this->process($folder);
                $hasInvalidPolygons = $hasInvalidPolygons ?: $processed[self::HAS_INVALID_POLYGONS_KEY];
                $places = array_merge($places, $processed[self::PLACES_KEY]);
            }
        }

        if (isset($kml->Placemark)) {
            foreach ($kml->Placemark as $placemark) {
                if ($placemark->MultiGeometry) {
                    $coords = $placemark->MultiGeometry->Polygon;
                } elseif ($placemark->MultiPolygon) {
                    $coords = $placemark->MultiPolygon->Polygon;
                } elseif ($placemark->Polygon) {
                    $coords = [$placemark->Polygon];
                } else {
                    continue;
                }

                $data = $this->createPolygonFromCoordSet($coords);
                $place = $data[self::PLACE_KEY];
                $hasInvalidPolygons = $hasInvalidPolygons ?: $data[self::HAS_INVALID_POLYGONS_KEY];

                if (!empty($place[self::PLACE_MARK_POLYGON_KEY]) && is_array($place[self::PLACE_MARK_POLYGON_KEY])) {
                    $place[self::PLACE_MARK_NAME_KEY] = $placemark->name->__toString();
                    $places[] = $place;
                }
            }
        }

        return [self::PLACES_KEY => $places, self::HAS_INVALID_POLYGONS_KEY => $hasInvalidPolygons];
    }

    /**
     * @param array|SimpleXMLElement $coords
     * @return array
     */
    private function createPolygonFromCoordSet($coords)
    {
        $hasInvalidPolygons = false;
        $place = [self::PLACE_MARK_POLYGON_KEY => []];
        foreach ($coords as $coord) {
            try {
                $place[self::PLACE_MARK_POLYGON_KEY][] = $this
                    ->processPolygon(trim($coord->outerBoundaryIs->LinearRing->coordinates));
            } catch (InvalidPolygonException $e) {
                $hasInvalidPolygons = true;
                continue;
            }
        }

        return [self::PLACE_KEY => $place, self::HAS_INVALID_POLYGONS_KEY => $hasInvalidPolygons];
    }

    /**
     * @param string $coords
     * @return string
     */
    private function processPolygon($coords)
    {
        $coords = explode(' ', $coords);

        if (count($coords) <= 3) {
            throw new InvalidPolygonException('Only received three points in the polygon');
        }

        foreach ($coords as &$value) {
            $value = preg_replace('/(-?\d+(\.\d+)?),(-?\d+(\.\d+)?),(-?\d+(\.\d+)?)/', '$1 $3', $value);
        }
    
        return '(' .implode(',', $coords) . ')';
    }
}
