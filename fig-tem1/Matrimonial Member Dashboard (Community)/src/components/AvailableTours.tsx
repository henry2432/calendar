import React, { useState } from 'react';
import { Heart, MessageCircle, MoreHorizontal, Filter, MapPin, Clock, Users, Star } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { Button } from './ui/button';
import { Badge } from './ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from './ui/dropdown-menu';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from './ui/select';

export function AvailableTours() {
  const [selectedFilter, setSelectedFilter] = useState('all');

  const tours = [
    {
      id: 1,
      name: 'Sunset SUP Tour',
      type: 'Stand-Up Paddle',
      location: 'Sunset Beach',
      price: '$45',
      duration: '2 hours',
      availability: 'Available',
      image: 'https://images.unsplash.com/photo-1759595812385-2d2e85095946?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxzdW5zZXQlMjBwYWRkbGUlMjBib2FyZCUyMHRvdXJ8ZW58MXx8fHwxNzY5OTYxOTc3fDA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral',
      rating: '4.9',
      groupSize: '8',
      isAvailable: true,
    },
    {
      id: 2,
      name: 'Snorkeling Adventure',
      type: 'Snorkeling',
      location: 'Coral Bay',
      price: '$55',
      duration: '3 hours',
      availability: 'Available',
      image: 'https://images.unsplash.com/photo-1486655643111-5a1741acd481?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxzbm9ya2VsaW5nJTIwdG91ciUyMG9jZWFufGVufDF8fHx8MTc2OTk2MTk3OHww&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral',
      rating: '5.0',
      groupSize: '12',
      isAvailable: true,
    },
    {
      id: 3,
      name: 'Kayak Island Tour',
      type: 'Kayaking',
      location: 'Marine Park',
      price: '$65',
      duration: '4 hours',
      availability: 'Limited',
      image: 'https://images.unsplash.com/photo-1603179333467-2368f9fc16a9?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxrYXlhayUyMHRvdXIlMjBhZHZlbnR1cmV8ZW58MXx8fHwxNzY5OTYxOTc4fDA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral',
      rating: '4.8',
      groupSize: '10',
      isAvailable: true,
    },
    {
      id: 4,
      name: 'Island Hopping Tour',
      type: 'Multi-Activity',
      location: 'Paradise Islands',
      price: '$85',
      duration: '6 hours',
      availability: 'Available',
      image: 'https://images.unsplash.com/photo-1760815630515-139b860d8feb?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxpc2xhbmQlMjBob3BwaW5nJTIwYm9hdCUyMHRvdXJ8ZW58MXx8fHwxNzY5OTYxOTc5fDA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral',
      rating: '5.0',
      groupSize: '15',
      isAvailable: false,
    },
  ];

  return (
    <Card>
      <CardHeader>
        <div className="flex items-center justify-between">
          <CardTitle>Available Tours</CardTitle>
          <div className="flex items-center gap-2">
            <Select value={selectedFilter} onValueChange={setSelectedFilter}>
              <SelectTrigger className="w-40">
                <Filter className="h-4 w-4 mr-2" />
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Tours</SelectItem>
                <SelectItem value="sup">SUP</SelectItem>
                <SelectItem value="snorkeling">Snorkeling</SelectItem>
                <SelectItem value="kayaking">Kayaking</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
      </CardHeader>
      <CardContent>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4">
          {tours.map((tour) => (
            <Card key={tour.id} className="hover:shadow-lg transition-shadow group overflow-hidden">
              <div className="aspect-video relative overflow-hidden">
                <img 
                  src={tour.image} 
                  alt={tour.name} 
                  className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                />
                {tour.isAvailable && (
                  <div className="absolute top-2 right-2">
                    <Badge className="bg-green-500 text-white">{tour.availability}</Badge>
                  </div>
                )}
                {!tour.isAvailable && (
                  <div className="absolute inset-0 bg-black/50 flex items-center justify-center">
                    <Badge variant="secondary" className="text-lg">Fully Booked</Badge>
                  </div>
                )}
              </div>
              <CardContent className="p-4">
                <div className="space-y-3">
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <h3 className="font-semibold">{tour.name}</h3>
                      <div className="flex items-center gap-1 text-sm text-muted-foreground mt-1">
                        <MapPin className="h-3 w-3" />
                        <span>{tour.location}</span>
                      </div>
                    </div>
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="icon" className="h-8 w-8">
                          <MoreHorizontal className="h-4 w-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuItem>View Details</DropdownMenuItem>
                        <DropdownMenuItem>Add to Wishlist</DropdownMenuItem>
                        <DropdownMenuItem>Check Reviews</DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </div>
                  
                  <div className="flex items-center gap-3 text-xs text-muted-foreground">
                    <div className="flex items-center gap-1">
                      <Clock className="h-3 w-3" />
                      {tour.duration}
                    </div>
                    <div className="flex items-center gap-1">
                      <Users className="h-3 w-3" />
                      Up to {tour.groupSize}
                    </div>
                    <div className="flex items-center gap-1">
                      <Star className="h-3 w-3 fill-amber-400 text-amber-400" />
                      {tour.rating}
                    </div>
                  </div>

                  <div className="flex items-center justify-between pt-2">
                    <div className="flex items-center gap-2">
                      <Badge variant="secondary" className="text-xs">
                        {tour.type}
                      </Badge>
                      <span className="text-lg font-semibold text-primary">
                        {tour.price}
                      </span>
                    </div>
                  </div>
                  
                  <div className="flex gap-2 pt-2">
                    <Button size="sm" className="flex-1" disabled={!tour.isAvailable}>
                      {tour.isAvailable ? 'Book Tour' : 'Fully Booked'}
                    </Button>
                    <Button size="sm" variant="outline" className="gap-1">
                      <Heart className="h-4 w-4" />
                    </Button>
                    <Button size="sm" variant="outline" className="gap-1">
                      <MessageCircle className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
        
        <div className="text-center mt-6">
          <Button variant="outline">View All Tours</Button>
        </div>
      </CardContent>
    </Card>
  );
}
