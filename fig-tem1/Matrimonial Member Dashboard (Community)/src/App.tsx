import React from 'react';
import { Header } from './components/Header';
import { WelcomeCard } from './components/WelcomeCard';
import { UpcomingBookings } from './components/UpcomingBookings';
import { AppleSlogan } from './components/AppleSlogan';
import { AvailableTours } from './components/AvailableTours';
import { RecommendedProducts } from './components/RecommendedProducts';

export default function App() {
  return (
    <div className="min-h-screen bg-background">
      {/* Header */}
      <Header />
      
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

      {/* Recommended Products - Full Width Black Section */}
      <RecommendedProducts />
    </div>
  );
}