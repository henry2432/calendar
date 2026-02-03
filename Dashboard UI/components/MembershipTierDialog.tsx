import React from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from './ui/dialog';

interface MembershipTierDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

export function MembershipTierDialog({ open, onOpenChange }: MembershipTierDialogProps) {
  const tiers = [
    {
      name: 'Bronze',
      icon: '⭐',
      requirement: '新註冊',
      description: '基礎會員',
      points: '1%積分',
    },
    {
      name: 'Silver',
      icon: '🥈',
      requirement: 'HKD $1,500',
      description: '銀卡會員',
      points: '2%積分',
    },
    {
      name: 'Gold',
      icon: '🥇',
      requirement: 'HKD $3,000',
      description: '金卡會員',
      points: '4%積分',
    },
    {
      name: 'Platinum',
      icon: '💎',
      requirement: 'HKD $5,000',
      description: '白金會員',
      points: '5%積分',
    },
  ];

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>會員等級專享</DialogTitle>
        </DialogHeader>
        
        <div className="space-y-6">
          {/* Membership Tiers Table */}
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b">
                  <th className="text-left py-3 px-4">等級</th>
                  <th className="text-left py-3 px-4">圖標</th>
                  <th className="text-left py-3 px-4">消費要求</th>
                  <th className="text-left py-3 px-4">等級名稱</th>
                  <th className="text-left py-3 px-4">積分回饋</th>
                </tr>
              </thead>
              <tbody>
                {tiers.map((tier, index) => (
                  <tr key={tier.name} className="border-b hover:bg-muted/50">
                    <td className="py-4 px-4 font-semibold">{tier.name}</td>
                    <td className="py-4 px-4 text-2xl">{tier.icon}</td>
                    <td className="py-4 px-4">{tier.requirement}</td>
                    <td className="py-4 px-4">{tier.description}</td>
                    <td className="py-4 px-4 text-primary font-semibold">{tier.points}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {/* Benefits */}
          <div className="space-y-4 bg-muted/30 p-6 rounded-lg">
            <h3 className="font-semibold">會員專享福利</h3>
            <ul className="space-y-3">
              <li className="flex items-start gap-3">
                <span className="text-primary mt-1">•</span>
                <span>每次升級體驗更多專屬優惠</span>
              </li>
              <li className="flex items-start gap-3">
                <span className="text-primary mt-1">•</span>
                <span>定期會員專屬優惠與活動</span>
              </li>
              <li className="flex items-start gap-3">
                <span className="text-primary mt-1">•</span>
                <span>累積消費金額越高，積分回饋越豐富</span>
              </li>
              <li className="flex items-start gap-3">
                <span className="text-primary mt-1">•</span>
                <span>優先預訂熱門旅程與活動</span>
              </li>
            </ul>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}
