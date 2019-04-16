package com.example.jackhalliday.activitytracker;

/**
 * Created by jackhalliday on 20/12/2016.
 */

public interface CallbackInterface {

    public void distance(float todayDistance, float totalDistance);

    public void steps(int todaySteps, int totalSteps);

    public void calories(int todayCalories, int totalCalories);

    public void tracking(int isTracking);

}
