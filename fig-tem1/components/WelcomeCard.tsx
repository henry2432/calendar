import React from 'react';
import { Edit, Camera, Award, Trophy } from 'lucide-react';
import { Card, CardContent } from './ui/card';
import { Button } from './ui/button';
import { Progress } from './ui/progress';
import { Avatar, AvatarFallback, AvatarImage } from './ui/avatar';

export function WelcomeCard() {
  return (
    <Card className="bg-gradient-to-r from-primary/5 to-primary/10 border-primary/20">
      <CardContent className="p-6">
        <div className="flex flex-col md:flex-row items-start md:items-center gap-6">
          {/* Profile Photo */}
          <div className="relative">
            <Avatar className="h-24 w-24 md:h-32 md:w-32">
              <AvatarImage src="https://images.unsplash.com/photo-1557110437-0bcd0a636d62?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHx1c2VyJTIwcHJvZmlsZSUyMGF2YXRhciUyMHNpbXBsZXxlbnwxfHx8fDE3Njk5NjE1MzF8MA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral" alt="Profile" />
              <AvatarFallback>JD</AvatarFallback>
            </Avatar>
            <Button size="icon" variant="secondary" className="absolute -bottom-2 -right-2 h-8 w-8 rounded-full">
              <Camera className="h-4 w-4" />
            </Button>
          </div>

          {/* Welcome Content */}
          <div className="flex-1 space-y-4">
            <div>
              <h1 className="text-2xl md:text-3xl">Welcome back, John!</h1>
              <p className="text-muted-foreground flex items-center gap-2">
                你今年已出海了5次
                <Award className="h-4 w-4 text-amber-500" />
              </p>
            </div>

            {/* Points Progress */}
            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <span className="text-sm">積分進度</span>
                <span className="text-sm text-primary">850 points</span>
              </div>
              <Progress value={68} className="h-2" />
              <p className="text-xs text-muted-foreground">
                150 more points to unlock Gold Membership rewards
              </p>
            </div>

            {/* Quick Actions */}
            <div className="flex flex-wrap gap-2">
              <Button size="sm" className="gap-2">
                <Edit className="h-4 w-4" />
                Edit Profile
              </Button>
              <Button size="sm" variant="outline" className="gap-2">
                <Trophy className="h-4 w-4" />
                查看更多成就徽章
              </Button>
              <Button size="sm" variant="outline">
                不同會員等級專享
              </Button>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}