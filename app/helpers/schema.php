<?php

if (!function_exists('renderEventSchema')) {
    /**
     * Gera um bloco JSON-LD (Schema.org Event) para um evento público.
     *
     * Retorna string vazia quando não há dados mínimos para um schema válido.
     */
    function renderEventSchema(array $event): string
    {
        $name = trim((string)($event['title'] ?? $event['name'] ?? ''));
        $description = trim((string)($event['description'] ?? $event['notes'] ?? ''));
        $startDate = event_schema_iso8601($event['start_date'] ?? null, $event['date'] ?? null, $event['time'] ?? null);

        // Campos mínimos para um Event útil ao Google.
        if ($name === '' || $startDate === '') {
            return '';
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $name,
            'startDate' => $startDate,
            'organizer' => [
                '@type' => 'Organization',
                'name' => 'Chorar de Rir',
                'url' => 'https://chorarderir.com',
            ],
        ];

        if ($description !== '') {
            $schema['description'] = $description;
        }

        $endDate = event_schema_iso8601($event['end_date'] ?? null, null, null);
        if ($endDate !== '') {
            $schema['endDate'] = $endDate;
        }

        $eventStatus = event_schema_status($event['event_status'] ?? $event['status'] ?? null);
        if ($eventStatus !== '') {
            $schema['eventStatus'] = $eventStatus;
        }

        $attendanceMode = event_schema_attendance_mode($event['event_attendance_mode'] ?? null);
        if ($attendanceMode !== '') {
            $schema['eventAttendanceMode'] = $attendanceMode;
        }

        $imageUrl = trim((string)($event['image'] ?? $event['image_url'] ?? $event['poster_url'] ?? ''));
        if ($imageUrl !== '') {
            $schema['image'] = [$imageUrl];
        }

        $locationName = trim((string)($event['venue'] ?? $event['location'] ?? ''));
        $street = trim((string)($event['address'] ?? ''));
        $city = trim((string)($event['city'] ?? ''));
        $country = trim((string)($event['country'] ?? 'PT'));
        if ($locationName !== '' || $street !== '' || $city !== '') {
            $address = ['@type' => 'PostalAddress'];
            if ($street !== '') {
                $address['streetAddress'] = $street;
            }
            if ($city !== '') {
                $address['addressLocality'] = $city;
            }
            if ($country !== '') {
                $address['addressCountry'] = $country;
            }

            $schema['location'] = [
                '@type' => 'Place',
                'name' => $locationName !== '' ? $locationName : ($city !== '' ? $city : 'Local do evento'),
                'address' => $address,
            ];
        }

        $offerUrl = trim((string)($event['ticket_url'] ?? $event['external_ticket_url'] ?? $event['public_url'] ?? $event['url'] ?? ''));
        $priceRaw = $event['price'] ?? $event['ticket_price'] ?? null;
        $price = is_numeric($priceRaw) ? (string)(0 + $priceRaw) : trim((string)$priceRaw);
        $currency = trim((string)($event['price_currency'] ?? 'EUR'));
        $availability = event_schema_availability($event['offer_availability'] ?? $event['availability'] ?? null, $event['reservations_open'] ?? null);

        $offer = ['@type' => 'Offer'];
        if ($offerUrl !== '') {
            $offer['url'] = $offerUrl;
        }
        if ($price !== '') {
            $offer['price'] = $price;
        }
        if ($currency !== '') {
            $offer['priceCurrency'] = $currency;
        }
        if ($availability !== '') {
            $offer['availability'] = $availability;
        }
        if (count($offer) > 1) {
            $schema['offers'] = $offer;
        }

        $performers = event_schema_performers($event);
        if ($performers !== []) {
            $schema['performer'] = $performers;
        }

        $json = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false || $json === 'null') {
            return '';
        }

        return "<script type=\"application/ld+json\">\n" . $json . "\n</script>";
    }

    function event_schema_iso8601($value, $date = null, $time = null): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        $raw = trim((string)$value);
        if ($raw === '' && $date !== null) {
            $dateValue = trim((string)$date);
            $timeValue = trim((string)$time);
            if ($dateValue !== '') {
                $raw = $dateValue . ($timeValue !== '' ? ' ' . $timeValue : ' 00:00:00');
            }
        }

        if ($raw === '') {
            return '';
        }

        try {
            $dateTime = new DateTime($raw);
            return $dateTime->format(DateTimeInterface::ATOM);
        } catch (Throwable $e) {
            return '';
        }
    }

    function event_schema_status($status): string
    {
        $value = strtolower(trim((string)$status));
        $map = [
            'scheduled' => 'https://schema.org/EventScheduled',
            'active' => 'https://schema.org/EventScheduled',
            'postponed' => 'https://schema.org/EventPostponed',
            'cancelled' => 'https://schema.org/EventCancelled',
            'canceled' => 'https://schema.org/EventCancelled',
            'movedonline' => 'https://schema.org/EventMovedOnline',
            'rescheduled' => 'https://schema.org/EventRescheduled',
        ];

        return $map[$value] ?? 'https://schema.org/EventScheduled';
    }

    function event_schema_attendance_mode($mode): string
    {
        $value = strtolower(trim((string)$mode));
        $map = [
            'offline' => 'https://schema.org/OfflineEventAttendanceMode',
            'online' => 'https://schema.org/OnlineEventAttendanceMode',
            'mixed' => 'https://schema.org/MixedEventAttendanceMode',
        ];

        return $map[$value] ?? 'https://schema.org/OfflineEventAttendanceMode';
    }

    function event_schema_availability($availability, $reservationsOpen = null): string
    {
        if ($availability !== null && trim((string)$availability) !== '') {
            $value = strtolower(trim((string)$availability));
            $map = [
                'instock' => 'https://schema.org/InStock',
                'in_stock' => 'https://schema.org/InStock',
                'onsale' => 'https://schema.org/InStock',
                'soldout' => 'https://schema.org/SoldOut',
                'sold_out' => 'https://schema.org/SoldOut',
                'presale' => 'https://schema.org/PreSale',
            ];
            return $map[$value] ?? '';
        }

        if ($reservationsOpen !== null) {
            return ((int)$reservationsOpen === 1) ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut';
        }

        return '';
    }

    function event_schema_performers(array $event): array
    {
        $performers = [];

        if (!empty($event['performers']) && is_array($event['performers'])) {
            foreach ($event['performers'] as $performer) {
                $name = trim((string)($performer['name'] ?? $performer['stage_name'] ?? $performer));
                if ($name !== '') {
                    $performers[] = ['@type' => 'Person', 'name' => $name];
                }
            }
        }

        if (empty($performers) && !empty($event['artists']) && is_array($event['artists'])) {
            foreach ($event['artists'] as $artist) {
                $name = trim((string)($artist['name'] ?? $artist['stage_name'] ?? $artist));
                if ($name !== '') {
                    $performers[] = ['@type' => 'Person', 'name' => $name];
                }
            }
        }

        return $performers;
    }
}
