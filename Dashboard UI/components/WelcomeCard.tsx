import React from 'react';
import { Edit, Award, Trophy } from 'lucide-react';
import { Card, CardContent } from './ui/card';
import { Button } from './ui/button';
import { Progress } from './ui/progress';
import { MembershipTierDialog } from './MembershipTierDialog';
import avatarImage from 'figma:asset/4b2b2ae6a44795ca30ccf988c48dcfee8529a22f.png';

export function WelcomeCard() {
  const [showMembershipDialog, setShowMembershipDialog] = React.useState(false);

  return (
    <>
      <Card className="overflow-hidden">
        <CardContent className="p-6">
          <div className="flex flex-col md:flex-row gap-6">
            {/* Profile Image */}
            <div className="flex-shrink-0">
              <div className="relative w-32 h-32 rounded-full overflow-hidden border-4 border-primary/20">
                <img
                  src={avatarImage}
                  alt="Profile"
                  className="w-full h-full object-cover"
                />
              </div>
              <div className="text-center mt-3">
                <p className="text-sm text-muted-foreground">目前擁有積分</p>
                <p className="text-xl font-semibold text-primary">850 分</p>
              </div>
            </div>

            {/* Welcome Content */}
            <div className="flex-1 space-y-4">
              <div>
                <div className="flex items-center gap-2">
                  <h1 className="text-2xl md:text-3xl">Welcome back, John!</h1>
                  <span className="text-2xl">🥈</span>
                </div>
                <p className="text-muted-foreground flex items-center gap-2">
                  目前會員等級：Silver
                </p>
                <p className="text-muted-foreground flex items-center gap-2 mt-1">
                  你今年已出海了5次
                  <Award className="h-4 w-4 text-amber-500" />
                </p>
              </div>

              {/* Upgrade Progress */}
              <div className="space-y-2">
                <div className="flex items-center justify-between">
                  <span className="text-sm">升級進度</span>
                  <span className="text-sm text-primary">$500 / $3,000</span>
                </div>
                <Progress value={17} className="h-2" />
                <p className="text-xs text-muted-foreground">
                  還需消費 $2,500 即可升級至金卡會員
                </p>
              </div>

              {/* Quick Actions */}
              <div className="flex flex-wrap gap-2">
                <Button size="sm" className="gap-2">
                  <Edit className="h-4 w-4" />
                  編輯個人資料
                </Button>
                <Button size="sm" variant="outline" className="gap-2">
                  <Trophy className="h-4 w-4" />
                  查看更多成就徽章
                </Button>
                <Button size="sm" variant="outline" onClick={() => setShowMembershipDialog(true)}>
                  不同會員等級專享
                </Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
      
      <MembershipTierDialog 
        open={showMembershipDialog} 
        onOpenChange={setShowMembershipDialog} 
      />
    </>
  );
}