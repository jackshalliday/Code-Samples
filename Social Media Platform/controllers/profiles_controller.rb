class ProfilesController < ApplicationController
  before_filter :authenticate_user!

  def index
    @profiles = User.page(params[:page]).per(4)
  end

  def show
    @user = User.find(params[:id])
  end



  def follower

    fol = Follow.new(from_node: current_user, to_node: @user, followed: true)

    respond_to do |format|
      if fol.save
        format.js { render :index, notice: 'You have followed the user' }
      else
        format.js { redirect_to post_path, notice: 'Error following the user.'}
      end
    end
  end

end