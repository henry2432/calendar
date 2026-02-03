import React from 'react';
import { Heart, MapPin, Clock, Users, Star } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { Button } from './ui/button';
import { Badge } from './ui/badge';
import tour1 from 'figma:asset/8d1409976a29c08d18adfa05d91e58bf8905c395.png';
import tour2 from 'figma:asset/c2fa00f98b97a6127062c84cd1e76b7370e10cd4.png';

export function AvailableTours() {
  const tours = [
    {
      id: 1,
      name: '白沙洲日落直立板團',
      type: '直立板',
      location: '白沙洲',
      price: '$580',
      duration: '3 小時',
      availability: '可預訂',
      image: tour1,
      rating: '5.0',
      groupSize: '16',
      isAvailable: true,
    },
    {
      id: 2,
      name: '雙人日出划槳體驗',
      type: '直立板',
      location: '翡翠灣',
      price: '$680',
      duration: '2.5 小時',
      availability: '可預訂',
      image: tour2,
      rating: '4.9',
      groupSize: '12',
      isAvailable: true,
    },
  ];

  return (
    <Card className="mb-8">
      <CardHeader>
        <CardTitle>推薦旅程</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {tours.map((tour) => (
            <Card key={tour.id} className="hover:shadow-lg transition-shadow group overflow-hidden">
              <div className="aspect-square relative overflow-hidden">
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
                    <Badge variant="secondary" className="text-lg">已滿額</Badge>
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
                  </div>
                  
                  <div className="flex items-center gap-3 text-xs text-muted-foreground">
                    <div className="flex items-center gap-1">
                      <Clock className="h-3 w-3" />
                      {tour.duration}
                    </div>
                    <div className="flex items-center gap-1">
                      <Users className="h-3 w-3" />
                      最多 {tour.groupSize} 人
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
                      {tour.isAvailable ? '立即預訂' : '已滿額'}
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      </CardContent>
    </Card>
  );
}