import React from 'react';
import { Calendar, MapPin, Clock, RefreshCw, X } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { Button } from './ui/button';
import { Badge } from './ui/badge';

export function UpcomingBookings() {
  const bookings = [
    {
      id: 1,
      kayakType: 'Single Kayak',
      location: 'Marina Bay',
      date: 'Feb 5, 2026',
      time: '10:00 AM - 2:00 PM',
      status: 'Confirmed',
      duration: '4 hours',
    },
    {
      id: 2,
      kayakType: 'Double Kayak',
      location: 'Sunset Beach',
      date: 'Feb 12, 2026',
      time: '3:00 PM - 6:00 PM',
      status: 'Confirmed',
      duration: '3 hours',
    },
    {
      id: 3,
      kayakType: 'Fishing Kayak',
      location: 'River Point',
      date: 'Feb 18, 2026',
      time: '8:00 AM - 12:00 PM',
      status: 'Pending',
      duration: '4 hours',
    },
  ];

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Calendar className="h-5 w-5" />
          Upcoming Bookings
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        {bookings.map((booking) => (
          <div
            key={booking.id}
            className="p-4 border border-border rounded-lg space-y-3 hover:shadow-md transition-shadow"
          >
            <div className="flex items-start justify-between">
              <div className="space-y-1">
                <h4 className="font-semibold">{booking.kayakType}</h4>
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                  <MapPin className="h-4 w-4" />
                  {booking.location}
                </div>
              </div>
              <Badge variant={booking.status === 'Confirmed' ? 'default' : 'secondary'}>
                {booking.status}
              </Badge>
            </div>

            <div className="flex items-center gap-4 text-sm text-muted-foreground">
              <div className="flex items-center gap-1">
                <Calendar className="h-4 w-4" />
                {booking.date}
              </div>
              <div className="flex items-center gap-1">
                <Clock className="h-4 w-4" />
                {booking.time}
              </div>
            </div>

            <div className="flex gap-2 pt-2">
              <Button size="sm" variant="outline" className="flex-1 gap-2">
                <RefreshCw className="h-4 w-4" />
                Re-schedule
              </Button>
              <Button size="sm" variant="outline" className="flex-1 gap-2 text-destructive hover:text-destructive">
                <X className="h-4 w-4" />
                Cancel
              </Button>
            </div>
          </div>
        ))}
      </CardContent>
    </Card>
  );
}
