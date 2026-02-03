import React from 'react';
import { WelcomeCard } from './components/WelcomeCard';
import { UpcomingBookings } from './components/UpcomingBookings';
import { AppleSlogan } from './components/AppleSlogan';
import { AvailableTours } from './components/AvailableTours';
import { RecommendedProducts } from './components/RecommendedProducts';
import { Toaster } from 'sonner@2.0.3';

export default function App() {
  return (
    <div className="min-h-screen bg-background">
      {/* Toast Notifications */}
      <Toaster position="top-center" richColors />
      
      {/* Main Content */}
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="space-y-8">
          {/* Welcome Section */}
          <WelcomeCard />
          
          {/* Upcoming Bookings */}
          <UpcomingBookings />
        </div>
      </main>

      {/* Apple-style Slogan Section */}
      <AppleSlogan />

      {/* Available Tours */}
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
        <AvailableTours />
      </main>

      {/* Recommended Products */}
      <RecommendedProducts />
    </div>
  );
}