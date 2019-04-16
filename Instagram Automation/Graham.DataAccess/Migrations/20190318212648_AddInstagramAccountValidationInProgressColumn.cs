using Microsoft.EntityFrameworkCore.Migrations;

namespace Graham.DataAccess.Migrations
{
    public partial class AddInstagramAccountValidationInProgressColumn : Migration
    {
        protected override void Up(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.DropColumn(
                name: "InstagramAccountValidated",
                table: "InstagramAccounts");

            migrationBuilder.AddColumn<bool>(
                name: "Validated",
                table: "InstagramAccounts",
                nullable: false,
                defaultValue: false);

            migrationBuilder.AddColumn<bool>(
                name: "ValidationInProgress",
                table: "InstagramAccounts",
                nullable: false,
                defaultValue: false);
        }

        protected override void Down(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.DropColumn(
                name: "Validated",
                table: "InstagramAccounts");

            migrationBuilder.DropColumn(
                name: "ValidationInProgress",
                table: "InstagramAccounts");

            migrationBuilder.AddColumn<bool>(
                name: "InstagramAccountValidated",
                table: "InstagramAccounts",
                nullable: false,
                defaultValue: false);
        }
    }
}
