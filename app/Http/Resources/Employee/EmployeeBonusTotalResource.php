<?php

namespace App\Http\Resources\Employee;

use App\Http\Resources\Bonus\BonusDegreeStageResource;
use App\Http\Resources\GeneralIdNameResource;
use App\Http\Resources\User\SectionResource;
use App\Http\Resources\User\UserLiteResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeBonusTotalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'isPerson' => $this->is_person,
            'dateWork' => $this->date_work,
            'getBonusEmployeeTotal' => $this->getBonusEmployeeTotal,
            //'UserId' => $this->user_id,
            //'UserName' => $this->User->name,
            //'SectionId' => $this->section_id,
            //'SectionName' => $this->Section->name,
            //'PositionId' => $this->employee_position_id,
            //'PositionName' => $this->EmployeePosition->name,
            //'TypeId' => $this->employee_type_id,
            //'TypeName' => $this->EmployeeType->name,
            'User' => new UserLiteResource($this->User),
            'Section' => new SectionResource($this->Section),
            'isMoveSection' => $this->is_move_section,
            'MoveSection' => new SectionResource($this->MoveSection),
            'EmployeePosition' => new EmployeePositionResource($this->EmployeePosition),
            'EmployeeCenter' => new EmployeeCenterResource($this->EmployeeCenter),
            'EmployeeType' => new EmployeeTypeResource($this->EmployeeType),
            'countItems' => count($this->outputVouchers),
            'Items' => $this->outputVouchers,
            'SumItems' => $this->outputVouchers,
            'number' => $this->number,
            'idCard' => $this->id_card,
            'telegramId' => $this->telegram,
            'numberLastBonus' => $this->number_last_bonus,
            'dateLastBonus' => $this->date_last_bonus,
            'dateNextBonus' => $this->date_next_bonus,
            'difNextBonusDate' => $this->getDifNextBonusDateAttribute(),
            'numberLastPromotion' => $this->number_last_promotion,
            'dateLastPromotion' => $this->date_last_promotion,
            'dateNextPromotion' => $this->date_next_promotion,
            'difNextPromotionDate' => $this->getDifNextPromotionDateAttribute(),
            'BonusJobTitle' => new GeneralIdNameResource($this->BonusJobTitle),
            'Study' => new GeneralIdNameResource($this->Study),
            'Certificate' => new GeneralIdNameResource($this->Certificate),
            'DegreeStage' => new BonusDegreeStageResource($this->DegreeStage),
        ];
    }

    public function getDifNextBonusDateAttribute(): int
    {
        // Assuming you want to calculate the difference in days
        return now()->diffInDays($this->date_next_bonus);
    }
    public function getDifNextPromotionDateAttribute(): int
    {
        // Assuming you want to calculate the difference in days
        return now()->diffInDays($this->date_next_promotion);
    }
}
