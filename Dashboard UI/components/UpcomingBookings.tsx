import React, { useState } from 'react';
import { Calendar, Clock, MoreHorizontal } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { Button } from './ui/button';
import { Badge } from './ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from './ui/dropdown-menu';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from './ui/dialog';
import { Calendar as CalendarComponent } from './ui/calendar';
import { toast } from 'sonner@2.0.3';

export function UpcomingBookings() {
  const [isRescheduleOpen, setIsRescheduleOpen] = useState(false);
  const [isCancelOpen, setIsCancelOpen] = useState(false);
  const [selectedBooking, setSelectedBooking] = useState<number | null>(null);
  const [selectedDate, setSelectedDate] = useState<Date | undefined>(undefined);

  const bookings = [
    {
      id: 1,
      activity: '皮划艇租賃',
      date: '2026年2月15日',
      time: '10:00 AM - 2:00 PM',
      status: '已確認',
      participants: 2,
    },
    {
      id: 2,
      activity: 'SUP 旅程',
      date: '2026年2月22日',
      time: '3:00 PM - 5:00 PM',
      status: '待確認',
      participants: 1,
    },
  ];

  const handleReschedule = (bookingId: number) => {
    setSelectedBooking(bookingId);
    setIsRescheduleOpen(true);
    setSelectedDate(undefined);
  };

  const handleConfirmReschedule = () => {
    // Here you would typically send the reschedule request to your backend
    console.log('Rescheduling booking', selectedBooking, 'to', selectedDate);
    setIsRescheduleOpen(false);
    setSelectedBooking(null);
    setSelectedDate(undefined);
    toast.success('預訂已成功改期');
  };

  const handleCancel = (bookingId: number) => {
    setSelectedBooking(bookingId);
    setIsCancelOpen(true);
  };

  const handleConfirmCancel = () => {
    // Here you would typically send the cancel request to your backend
    console.log('Canceling booking', selectedBooking);
    setIsCancelOpen(false);
    setSelectedBooking(null);
    toast.success('預訂已成功取消，積分已退回您的帳戶');
  };

  return (
    <>
      <Card>
        <CardHeader>
          <CardTitle>即將到來的預訂</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {bookings.map((booking) => (
              <Card key={booking.id}>
                <CardContent className="p-4">
                  <div className="flex items-start justify-between">
                    <div className="flex-1 space-y-2">
                      <div className="flex items-center justify-between">
                        <h3 className="font-semibold">{booking.activity}</h3>
                        <Badge variant={booking.status === '已確認' ? 'default' : 'secondary'}>
                          {booking.status}
                        </Badge>
                      </div>
                      
                      <div className="space-y-1 text-sm text-muted-foreground">
                        <div className="flex items-center gap-2">
                          <Calendar className="h-4 w-4" />
                          <span>{booking.date}</span>
                        </div>
                        <div className="flex items-center gap-2">
                          <Clock className="h-4 w-4" />
                          <span>{booking.time}</span>
                        </div>
                      </div>

                      <div className="flex gap-2 pt-2">
                        <Button 
                          size="sm" 
                          variant="outline"
                          onClick={() => handleReschedule(booking.id)}
                        >
                          改期
                        </Button>
                        <Button 
                          size="sm" 
                          variant="outline"
                          onClick={() => handleCancel(booking.id)}
                        >
                          取消
                        </Button>
                      </div>
                    </div>

                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="icon" className="h-8 w-8">
                          <MoreHorizontal className="h-4 w-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuItem>下載收據</DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        </CardContent>
      </Card>

      <Dialog open={isRescheduleOpen} onOpenChange={setIsRescheduleOpen}>
        <DialogContent className="sm:max-w-[500px]">
          <DialogHeader>
            <DialogTitle>改期預訂</DialogTitle>
            <DialogDescription>
              請選擇新的日期，我們將為您重新安排活動時間。
            </DialogDescription>
          </DialogHeader>
          
          <div className="flex justify-center py-4">
            <CalendarComponent
              mode="single"
              selected={selectedDate}
              onSelect={setSelectedDate}
              disabled={(date) => 
                date < new Date() || date < new Date(new Date().setDate(new Date().getDate() - 1))
              }
              className="rounded-md border"
            />
          </div>

          <DialogFooter>
            <Button 
              variant="outline" 
              onClick={() => setIsRescheduleOpen(false)}
            >
              取消
            </Button>
            <Button 
              onClick={handleConfirmReschedule}
              disabled={!selectedDate}
            >
              確認改期
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={isCancelOpen} onOpenChange={setIsCancelOpen}>
        <DialogContent className="sm:max-w-[500px]">
          <DialogHeader>
            <DialogTitle>取消預訂</DialogTitle>
            <DialogDescription>
              確認要取消這筆預訂嗎？
            </DialogDescription>
          </DialogHeader>
          
          <DialogFooter>
            <Button 
              variant="outline" 
              onClick={() => setIsCancelOpen(false)}
            >
              取消
            </Button>
            <Button 
              onClick={handleConfirmCancel}
            >
              確認取消
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}